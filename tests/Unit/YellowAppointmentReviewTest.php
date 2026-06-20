<?php

namespace Tests\Unit;

use App\Models\Appointment;
use Tests\TestCase;

class YellowAppointmentReviewTest extends TestCase
{
    public function test_yellow_pending_appointment_requires_professional_review(): void
    {
        $appointment = new Appointment([
            'google_color_id' => Appointment::GOOGLE_YELLOW_COLOR_ID,
            'nimbus_status' => 'pending',
        ]);

        $this->assertTrue($appointment->requiresProfessionalReview());
    }

    public function test_confirmed_review_or_non_yellow_appointment_does_not_require_review(): void
    {
        $confirmed = new Appointment([
            'google_color_id' => Appointment::GOOGLE_YELLOW_COLOR_ID,
            'nimbus_status' => 'pending',
            'professional_review_decision' => 'confirmed',
        ]);
        $regular = new Appointment([
            'google_color_id' => '10',
            'nimbus_status' => 'pending',
        ]);

        $this->assertFalse($confirmed->requiresProfessionalReview());
        $this->assertFalse($regular->requiresProfessionalReview());
    }
}
