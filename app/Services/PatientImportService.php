<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PatientImportService
{
    /**
     * Canonical header aliases (normalized: lowercase, stripped accents).
     */
    protected array $headerAliases = [
        'code' => ['code', 'codigo', 'código', 'cod'],
        'name' => ['name', 'nombre', 'nombre completo', 'fullname', 'full name'],
        'email' => ['email', 'correo', 'correo electronico', 'correo electrónico', 'e-mail', 'mail'],
        'phone' => ['phone', 'telefono', 'teléfono', 'móvil', 'movil', 'mobile', 'tel'],
    ];

    /**
     * Import patients from a CSV file path.
     */
    public function importFromFile(string $path, User $user): array
    {
        $handle = @fopen($path, 'r');
        if (!$handle) {
            return $this->emptyReport(['No se pudo leer el archivo.']);
        }

        $rows = [];
        $delimiter = $this->detectDelimiterFromFile($path);
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $this->importRows($rows, $user);
    }

    /**
     * Import patients from pasted text (CSV or TSV).
     */
    public function importFromPaste(string $text, User $user): array
    {
        $text = trim($text);
        if ($text === '') {
            return $this->emptyReport(['No se pegó ningún contenido.']);
        }

        $delimiter = $this->detectDelimiterFromString($text);
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line, $delimiter);
        }

        return $this->importRows($rows, $user);
    }

    /**
     * Core import loop.
     */
    protected function importRows(array $rows, User $user): array
    {
        $report = $this->emptyReport();

        if (empty($rows)) {
            return $report;
        }

        $mapping = $this->resolveMapping($rows[0]);
        if ($mapping['header']) {
            array_shift($rows);
        }

        // Preload user's existing patients to match duplicates efficiently
        $existing = Patient::where('user_id', $user->id)
            ->get(['id', 'code', 'email', 'phone']);

        $existingByCode = [];
        $existingByEmail = [];
        $existingByPhone = [];
        foreach ($existing as $p) {
            if ($p->code) {
                $existingByCode[strtoupper($p->code)] = $p->id;
            }
            if ($p->email) {
                $existingByEmail[strtolower($p->email)] = $p->id;
            }
            $normalizedPhone = $this->normalizePhone($p->phone);
            if ($normalizedPhone) {
                $existingByPhone[$normalizedPhone] = $p->id;
            }
        }

        // Track in-batch duplicates too
        $seenCodes = [];
        $seenEmails = [];
        $seenPhones = [];

        $lineNumber = $mapping['header'] ? 1 : 0;

        foreach ($rows as $row) {
            $lineNumber++;

            if (!is_array($row) || $this->rowIsEmpty($row)) {
                continue;
            }

            $data = $this->extractRow($row, $mapping['columns']);

            if (empty($data['code']) && empty($data['name'])) {
                $report['ignored'][] = "Línea {$lineNumber}: fila vacía o sin datos suficientes";
                continue;
            }

            if (empty($data['name'])) {
                $report['ignored'][] = "Línea {$lineNumber}: falta el nombre";
                continue;
            }

            if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $report['ignored'][] = "Línea {$lineNumber}: email inválido ({$data['email']})";
                continue;
            }

            // Duplicate detection (existing DB + already seen in this batch)
            $duplicateReason = $this->findDuplicateReason(
                $data,
                $existingByCode,
                $existingByEmail,
                $existingByPhone,
                $seenCodes,
                $seenEmails,
                $seenPhones
            );

            if ($duplicateReason) {
                $report['duplicates'][] = "Línea {$lineNumber}: {$duplicateReason}";
                continue;
            }

            try {
                Patient::create([
                    'user_id' => $user->id,
                    'code' => $data['code'] ?: null,
                    'name' => $data['name'],
                    'email' => $data['email'] ?: null,
                    'phone' => $data['phone'] ?: null,
                    'preferred_channel' => 'email',
                    'consent_email' => !empty($data['email']),
                    'consent_sms' => !empty($data['phone']),
                ]);

                $report['created']++;

                if ($data['code']) {
                    $seenCodes[$data['code']] = true;
                }
                if ($data['email']) {
                    $seenEmails[strtolower($data['email'])] = true;
                }
                if ($data['phone']) {
                    $seenPhones[$data['phone']] = true;
                }
            } catch (\Exception $e) {
                Log::error("PatientImportService error line {$lineNumber}: " . $e->getMessage());
                $report['ignored'][] = "Línea {$lineNumber}: error al guardar";
            }
        }

        return $report;
    }

    /**
     * Resolve column mapping from the first row.
     */
    protected function resolveMapping(array $firstRow): array
    {
        $normalized = array_map(fn ($cell) => $this->normalizeHeader((string) $cell), $firstRow);

        $columns = [
            'code' => null,
            'name' => null,
            'email' => null,
            'phone' => null,
        ];

        foreach ($normalized as $index => $value) {
            foreach ($this->headerAliases as $field => $aliases) {
                if (in_array($value, $aliases, true) && $columns[$field] === null) {
                    $columns[$field] = $index;
                }
            }
        }

        $isHeader = count(array_filter($columns, fn ($i) => $i !== null)) >= 2;

        if (!$isHeader) {
            // Default positional mapping: code, name, email, phone
            $columns = [
                'code' => 0,
                'name' => 1,
                'email' => 2,
                'phone' => 3,
            ];
        }

        return [
            'header' => $isHeader,
            'columns' => $columns,
        ];
    }

    /**
     * Extract and normalize a row's data.
     */
    protected function extractRow(array $row, array $columns): array
    {
        $get = fn ($idx) => $idx !== null ? ($row[$idx] ?? '') : '';

        $code = strtoupper(trim((string) $get($columns['code'])));
        $name = $this->cleanName((string) $get($columns['name']));
        $email = strtolower(trim((string) $get($columns['email'])));
        $phone = $this->normalizePhone((string) $get($columns['phone']));

        return compact('code', 'name', 'email', 'phone');
    }

    /**
     * Detect duplicates against existing DB records and current batch.
     */
    protected function findDuplicateReason(
        array $data,
        array $existingByCode,
        array $existingByEmail,
        array $existingByPhone,
        array $seenCodes,
        array $seenEmails,
        array $seenPhones
    ): ?string {
        if ($data['code']) {
            if (isset($existingByCode[$data['code']])) {
                return "código '{$data['code']}' ya existe";
            }
            if (isset($seenCodes[$data['code']])) {
                return "código '{$data['code']}' duplicado en el archivo";
            }
        }

        if ($data['email']) {
            $emailKey = strtolower($data['email']);
            if (isset($existingByEmail[$emailKey])) {
                return "email '{$data['email']}' ya existe";
            }
            if (isset($seenEmails[$emailKey])) {
                return "email '{$data['email']}' duplicado en el archivo";
            }
        }

        if ($data['phone']) {
            if (isset($existingByPhone[$data['phone']])) {
                return "teléfono '{$data['phone']}' ya existe";
            }
            if (isset($seenPhones[$data['phone']])) {
                return "teléfono '{$data['phone']}' duplicado en el archivo";
            }
        }

        return null;
    }

    /**
     * Normalize phone to E.164-ish format.
     */
    public function normalizePhone(?string $phone): string
    {
        if (!$phone) {
            return '';
        }

        $phone = trim($phone);
        // Keep leading +, strip everything else non-digit
        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return '';
        }

        if ($hasPlus) {
            return '+' . $digits;
        }

        // International "00" prefix (e.g. 0034..., 0044..., 0033...)
        if (str_starts_with($digits, '00') && strlen($digits) > 5) {
            return '+' . substr($digits, 2);
        }

        // Spanish mobile/landline default when 9 digits
        if (strlen($digits) === 9) {
            return '+34' . $digits;
        }

        // 34 + 9 digits (Spain without leading +)
        if (strlen($digits) === 11 && str_starts_with($digits, '34')) {
            return '+' . $digits;
        }

        // Fallback: assume already-international if long enough, prefix +
        if (strlen($digits) >= 10) {
            return '+' . $digits;
        }

        // Too short to guess — return digits as-is
        return $digits;
    }

    protected function cleanName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        return $name ?? '';
    }

    protected function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = strtr($header, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n',
        ]);
        return $header;
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    protected function detectDelimiterFromFile(string $path): string
    {
        $handle = @fopen($path, 'r');
        if (!$handle) {
            return ',';
        }
        $sample = fread($handle, 4096) ?: '';
        fclose($handle);

        return $this->detectDelimiterFromString($sample);
    }

    protected function detectDelimiterFromString(string $text): string
    {
        $candidates = ["\t", ';', ',', '|'];
        $best = ',';
        $bestCount = -1;
        foreach ($candidates as $delim) {
            $count = substr_count($text, $delim);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $delim;
            }
        }
        return $best;
    }

    protected function emptyReport(array $errors = []): array
    {
        return [
            'created' => 0,
            'duplicates' => [],
            'ignored' => $errors,
        ];
    }
}
