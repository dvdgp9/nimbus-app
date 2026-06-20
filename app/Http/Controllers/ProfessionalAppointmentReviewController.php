<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\YellowAppointmentReviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionalAppointmentReviewController extends Controller
{
    public function __construct(private YellowAppointmentReviewService $reviews) {}

    public function show(Appointment $appointment, string $decision): View
    {
        abort_unless(in_array($decision, ['confirm', 'cancel'], true), 404);

        return view('professional-review.show', compact('appointment', 'decision'));
    }

    public function decide(Request $request, Appointment $appointment, string $decision): View
    {
        abort_unless(in_array($decision, ['confirm', 'cancel'], true), 404);

        $success = $decision === 'confirm'
            ? $this->reviews->confirm($appointment)
            : $this->reviews->cancel($appointment);

        return view('professional-review.result', compact('appointment', 'decision', 'success'));
    }
}
