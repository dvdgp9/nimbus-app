<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OnboardingController extends Controller
{
    /**
     * Onboarding step definitions
     */
    const STEP_WELCOME = 1;
    const STEP_IMPORT_PATIENTS = 2;
    const STEP_CONNECT_CALENDAR = 3;
    const STEP_CONFIGURE = 4;
    const STEP_COMPLETE = 5;

    /**
     * Show current onboarding step
     */
    public function index()
    {
        $user = auth()->user();
        $step = $user->onboarding_step ?: self::STEP_WELCOME;

        return $this->showStep($step);
    }

    /**
     * Show specific step
     */
    protected function showStep(int $step)
    {
        $user = auth()->user();

        return match ($step) {
            self::STEP_WELCOME => view('onboarding.welcome'),
            self::STEP_IMPORT_PATIENTS => view('onboarding.import-patients', [
                'patients' => $user->patients()->orderBy('name')->get(),
            ]),
            self::STEP_CONNECT_CALENDAR => view('onboarding.connect-calendar', [
                'isConnected' => $this->hasConnectedCalendar(),
            ]),
            self::STEP_CONFIGURE => view('onboarding.configure', [
                'emailTemplates' => $user->messageTemplates()->where('channel', 'email')->get(),
                'smsTemplates' => $user->messageTemplates()->where('channel', 'sms')->get(),
            ]),
            self::STEP_COMPLETE => view('onboarding.complete', [
                'patientsCount' => $user->patients()->count(),
                'isConnected' => $this->hasConnectedCalendar(),
            ]),
            default => view('onboarding.welcome'),
        };
    }

    /**
     * Go to next step
     */
    public function nextStep(Request $request)
    {
        $user = auth()->user();
        $currentStep = $user->onboarding_step ?: self::STEP_WELCOME;
        $nextStep = min($currentStep + 1, self::STEP_COMPLETE);

        $user->update(['onboarding_step' => $nextStep]);

        return redirect()->route('onboarding.index');
    }

    /**
     * Go to previous step
     */
    public function previousStep(Request $request)
    {
        $user = auth()->user();
        $currentStep = $user->onboarding_step ?: self::STEP_WELCOME;
        $prevStep = max($currentStep - 1, self::STEP_WELCOME);

        $user->update(['onboarding_step' => $prevStep]);

        return redirect()->route('onboarding.index');
    }

    /**
     * Skip to specific step
     */
    public function goToStep(Request $request, int $step)
    {
        $user = auth()->user();
        $step = max(self::STEP_WELCOME, min($step, self::STEP_COMPLETE));

        $user->update(['onboarding_step' => $step]);

        return redirect()->route('onboarding.index');
    }

    /**
     * Import patients from CSV
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->withErrors(['csv_file' => 'No se pudo leer el archivo']);
        }

        $user = auth()->user();
        $imported = 0;
        $errors = [];
        $lineNumber = 0;

        // Skip header if present
        $firstLine = fgetcsv($handle, 1000, ',');
        $lineNumber++;

        // Check if first line looks like a header
        $isHeader = $this->looksLikeHeader($firstLine);
        if (!$isHeader) {
            // First line is data, process it
            $result = $this->processPatientRow($firstLine, $user, $lineNumber);
            if ($result === true) {
                $imported++;
            } elseif ($result !== false) {
                $errors[] = $result;
            }
        }

        // Process remaining rows
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNumber++;
            
            if (count($row) < 2) {
                continue; // Skip empty or incomplete rows
            }

            $result = $this->processPatientRow($row, $user, $lineNumber);
            if ($result === true) {
                $imported++;
            } elseif ($result !== false) {
                $errors[] = $result;
            }
        }

        fclose($handle);

        $message = "Se importaron {$imported} pacientes correctamente.";
        if (count($errors) > 0) {
            $message .= " " . count($errors) . " filas con errores.";
        }

        return back()->with('success', $message)->with('import_errors', $errors);
    }

    /**
     * Check if row looks like a CSV header
     */
    protected function looksLikeHeader(array $row): bool
    {
        $headerKeywords = ['codigo', 'code', 'nombre', 'name', 'email', 'correo', 'telefono', 'phone'];
        $firstCell = strtolower(trim($row[0] ?? ''));

        foreach ($headerKeywords as $keyword) {
            if (str_contains($firstCell, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process a single patient row from CSV
     */
    protected function processPatientRow(array $row, $user, int $lineNumber): bool|string
    {
        // Expected format: code, name, email, phone (phone optional)
        $code = strtoupper(trim($row[0] ?? ''));
        $name = trim($row[1] ?? '');
        $email = trim($row[2] ?? '');
        $phone = trim($row[3] ?? '');

        // Validate required fields
        if (empty($code) || empty($name)) {
            return "Línea {$lineNumber}: Código y nombre son obligatorios";
        }

        // Check for duplicate code
        if ($user->patients()->where('code', $code)->exists()) {
            return "Línea {$lineNumber}: El código '{$code}' ya existe";
        }

        // Validate email if provided
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Línea {$lineNumber}: Email inválido '{$email}'";
        }

        try {
            Patient::create([
                'user_id' => $user->id,
                'code' => $code,
                'name' => $name,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'consent_email' => !empty($email),
                'consent_sms' => !empty($phone),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Error importing patient at line {$lineNumber}: " . $e->getMessage());
            return "Línea {$lineNumber}: Error al guardar";
        }
    }

    /**
     * Add single patient during onboarding
     */
    public function addPatient(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = auth()->user();
        $code = strtoupper($validated['code']);

        // Check for duplicate
        if ($user->patients()->where('code', $code)->exists()) {
            return back()->withErrors(['code' => 'Este código ya existe']);
        }

        Patient::create([
            'user_id' => $user->id,
            'code' => $code,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'consent_email' => !empty($validated['email']),
            'consent_sms' => !empty($validated['phone']),
        ]);

        return back()->with('success', 'Paciente añadido correctamente');
    }

    /**
     * Delete patient during onboarding
     */
    public function deletePatient(Patient $patient)
    {
        if ($patient->user_id !== auth()->id()) {
            abort(403);
        }

        $patient->delete();

        return back()->with('success', 'Paciente eliminado');
    }

    /**
     * Complete onboarding
     */
    public function complete(Request $request)
    {
        $user = auth()->user();
        $user->completeOnboarding();

        return redirect()->route('home')->with('success', '¡Bienvenida a Nimbus! Tu cuenta está lista.');
    }

    /**
     * Check if user has connected a Google Calendar
     */
    protected function hasConnectedCalendar(): bool
    {
        return DB::table('google_tokens')
            ->where('user_id', auth()->id())
            ->exists();
    }

    /**
     * Skip onboarding (for existing users)
     */
    public function skip(Request $request)
    {
        $user = auth()->user();
        $user->completeOnboarding();

        return redirect()->route('home');
    }
}
