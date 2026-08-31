<?php

namespace App\Services;

final class SmsSegmentCalculator
{
    /**
     * Estimate Acumbamail billing units and segments for one SMS body.
     *
     * @return array{encoding: 'standard'|'unicode', units: int, segments: int}
     */
    public static function analyse(string $message): array
    {
        // Acumbamail explicitly bills accents, ñ and emoji as Unicode. The
        // euro sign remains in its documented two-unit extension list.
        $isUnicode = preg_match('/[^\x00-\x7F€]/u', $message) === 1;

        if ($isUnicode) {
            // Operators count UTF-16 units, so supplementary emoji consume two.
            $units = intdiv(strlen(mb_convert_encoding($message, 'UTF-16BE', 'UTF-8')), 2);

            return [
                'encoding' => 'unicode',
                'units' => $units,
                'segments' => self::segments($units, 70, 67),
            ];
        }

        $units = mb_strlen($message);
        preg_match_all('/[\^{}\[\]~|\\\\€]/u', $message, $extensionCharacters);
        $units += count($extensionCharacters[0]);

        return [
            'encoding' => 'standard',
            'units' => $units,
            'segments' => self::segments($units, 160, 153),
        ];
    }

    private static function segments(int $units, int $singleLimit, int $concatenatedLimit): int
    {
        if ($units <= $singleLimit) {
            return 1;
        }

        return 1 + (int) ceil(($units - $singleLimit) / $concatenatedLimit);
    }
}
