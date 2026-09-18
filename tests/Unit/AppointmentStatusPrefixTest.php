<?php

namespace Tests\Unit;

use App\Models\Appointment;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Nimbus writes "OK - ", "X - " and "? - " at the start of the Google Calendar
 * title. Nothing that reads the patient or message code from a title may take
 * them for part of it.
 */
class AppointmentStatusPrefixTest extends TestCase
{
    #[DataProvider('prefixedTitles')]
    public function test_a_status_prefix_never_hides_the_patient_code_or_name(string $title): void
    {
        $appointment = new Appointment(['summary' => $title]);

        $this->assertSame('AB12', $appointment->suggested_patient_code);
        $this->assertSame('Ana', $appointment->suggested_patient_name);
    }

    public static function prefixedTitles(): array
    {
        return [
            'no prefix' => ['AB12 - Ana'],
            'confirmed' => ['OK - AB12 - Ana'],
            'cancelled' => ['X - AB12 - Ana'],
            'wants to change' => ['? - AB12 - Ana'],
            'typed by hand' => ['ok-AB12 - Ana'],
            'stacked' => ['OK - ? - AB12 - Ana'],
        ];
    }

    public function test_a_status_prefix_does_not_turn_the_patient_code_into_a_message_code(): void
    {
        $this->assertNull((new Appointment(['summary' => 'AB12']))->suggested_message_code);
        $this->assertNull((new Appointment(['summary' => '? - AB12']))->suggested_message_code);
        $this->assertNull((new Appointment(['summary' => 'OK - AB12']))->suggested_message_code);
        $this->assertSame('BP', (new Appointment(['summary' => 'X - AB12 Cita BP']))->suggested_message_code);
    }

    public function test_titles_that_only_start_like_a_prefix_are_left_alone(): void
    {
        $this->assertSame('XAVI', (new Appointment(['summary' => 'XAVI - Sesión']))->suggested_patient_code);
        $this->assertSame('OKAPI', (new Appointment(['summary' => 'Okapi - Sesión']))->suggested_patient_code);
        $this->assertSame('X12', (new Appointment(['summary' => 'X12 Sesión']))->suggested_patient_code);
    }

    public function test_a_new_status_prefix_replaces_the_previous_one(): void
    {
        $this->assertSame('? - AB12 - Ana', Appointment::withStatusPrefix('AB12 - Ana', Appointment::TITLE_PREFIX_RESCHEDULE));
        $this->assertSame('OK - AB12 - Ana', Appointment::withStatusPrefix('? - AB12 - Ana', Appointment::TITLE_PREFIX_CONFIRMED));
        $this->assertSame('X - AB12 - Ana', Appointment::withStatusPrefix('OK - AB12 - Ana', Appointment::TITLE_PREFIX_CANCELLED));
        $this->assertSame('X - AB12 - Ana', Appointment::withStatusPrefix('X - AB12 - Ana', Appointment::TITLE_PREFIX_CANCELLED));
        $this->assertSame('X - AB12 - Ana', Appointment::withStatusPrefix('OK - ? - AB12 - Ana', Appointment::TITLE_PREFIX_CANCELLED));
    }
}
