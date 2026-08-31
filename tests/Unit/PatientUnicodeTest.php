<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use App\Services\PatientImportService;
use PHPUnit\Framework\TestCase;

class PatientUnicodeTest extends TestCase
{
    public function test_appointment_extracts_an_accented_code_and_name(): void
    {
        $appointment = new Appointment([
            'summary' => 'áñ12 - María José',
        ]);

        $this->assertSame('ÁÑ12', $appointment->suggested_patient_code);
        $this->assertSame('María José', $appointment->suggested_patient_name);
    }

    public function test_google_calendar_extractor_accepts_unicode_letters(): void
    {
        $method = new \ReflectionMethod(GoogleCalendarService::class, 'extractPatientCode');

        $this->assertSame('ÁÑ12', $method->invoke(new GoogleCalendarService, '  áñ12 - Sesión  '));
    }

    public function test_patient_import_uppercases_accented_codes_and_preserves_names(): void
    {
        $method = new \ReflectionMethod(PatientImportService::class, 'extractRow');
        $data = $method->invoke(new PatientImportService, ['áñ12', 'María José', '', ''], [
            'code' => 0,
            'name' => 1,
            'email' => 2,
            'phone' => 3,
        ]);

        $this->assertSame('ÁÑ12', $data['code']);
        $this->assertSame('María José', $data['name']);
    }
}
