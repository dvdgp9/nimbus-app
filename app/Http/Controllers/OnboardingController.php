<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Services\FirstSessionService;
use App\Services\GoogleCalendarService;
use App\Services\PatientImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function __construct(
        private GoogleCalendarService $calendar,
        private PatientImportService $patientImporter,
        private FirstSessionService $firstSessions
    ) {}

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
                'hasGoogleAccount' => $this->hasConnectedGoogleAccount(),
                'hasConfiguredCalendars' => $this->hasConfiguredCalendars(),
                'connectedAccountEmail' => $this->getConnectedAccountEmail(),
            ]),
            self::STEP_CONFIGURE => view('onboarding.configure', [
                'emailTemplates' => $user->messageTemplates()->where('channel', 'email')->get(),
                'smsTemplates' => $user->messageTemplates()->where('channel', 'sms')->get(),
                'suggestedCodes' => $this->getSuggestedCodes(),
            ]),
            self::STEP_COMPLETE => view('onboarding.complete', [
                'patientsCount' => $user->patients()->count(),
                'isConnected' => $this->hasConfiguredCalendars(),
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

        if ($currentStep === self::STEP_CONNECT_CALENDAR && $this->hasConfiguredCalendars()) {
            $this->syncInitialAppointments();
        }

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

        $report = $this->patientImporter->importFromFile(
            $request->file('csv_file')->getRealPath(),
            auth()->user()
        );

        return back()->with($this->flashFromReport($report));
    }

    /**
     * Import patients from pasted text (TSV/CSV)
     */
    public function importPaste(Request $request)
    {
        $request->validate([
            'paste' => 'required|string|max:100000',
        ]);

        $report = $this->patientImporter->importFromPaste(
            $request->input('paste'),
            auth()->user()
        );

        return back()->with($this->flashFromReport($report));
    }

    /**
     * Build flash data from an import report
     */
    protected function flashFromReport(array $report): array
    {
        $summary = "Se crearon {$report['created']} pacientes.";
        if (count($report['duplicates']) > 0) {
            $summary .= ' ' . count($report['duplicates']) . ' duplicados ignorados.';
        }
        if (count($report['ignored']) > 0) {
            $summary .= ' ' . count($report['ignored']) . ' filas con errores.';
        }

        return [
            'success' => $summary,
            'import_duplicates' => $report['duplicates'],
            'import_errors' => $report['ignored'],
        ];
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

        $phone = $this->patientImporter->normalizePhone($validated['phone'] ?? null);

        Patient::create([
            'user_id' => $user->id,
            'code' => $code,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $phone ?: null,
            'preferred_channel' => 'email',
            'consent_email' => !empty($validated['email']),
            'consent_sms' => !empty($phone),
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
    protected function hasConnectedGoogleAccount(): bool
    {
        return DB::table('google_tokens')
            ->where('user_id', auth()->id())
            ->exists();
    }

    protected function hasConfiguredCalendars(): bool
    {
        return DB::table('connected_calendars')
            ->where('user_id', auth()->id())
            ->where('enabled', 1)
            ->exists();
    }

    protected function getConnectedAccountEmail(): ?string
    {
        return DB::table('google_tokens')
            ->where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->value('account_email');
    }

    protected function syncInitialAppointments(): void
    {
        $accountEmail = $this->getConnectedAccountEmail();
        if (!$accountEmail) {
            return;
        }

        $calendarIds = DB::table('connected_calendars')
            ->where('user_id', auth()->id())
            ->where('account_email', $accountEmail)
            ->where('enabled', 1)
            ->pluck('calendar_id')
            ->all();

        if (empty($calendarIds)) {
            return;
        }

        try {
            $hoursAhead = 720;
            $events = $this->calendar->listUpcomingEvents($accountEmail, $hoursAhead, $calendarIds, auth()->id());
            $this->calendar->syncAppointments($events, auth()->id(), $calendarIds, $hoursAhead);
        } catch (\Exception $e) {
            Log::warning('Initial onboarding sync failed: ' . $e->getMessage());
        }
    }

    protected function getSuggestedCodes(): array
    {
        $calendarIds = DB::table('connected_calendars')
            ->where('user_id', auth()->id())
            ->where('enabled', 1)
            ->pluck('calendar_id')
            ->all();

        if (empty($calendarIds)) {
            return [];
        }

        $completedCodes = MessageTemplate::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('code')
            ->get(['code', 'channel'])
            ->groupBy(fn ($template) => strtoupper($template->code))
            ->filter(function ($templates) {
                $channels = $templates->pluck('channel')->unique();

                return $channels->contains('email') && $channels->contains('sms');
            })
            ->keys()
            ->all();

        return Appointment::query()
            ->whereIn('calendar_id', $calendarIds)
            ->where('start_at', '>=', now())
            ->where('start_at', '<=', now()->addDays(30))
            ->get()
            ->reject(fn ($appointment) => $this->firstSessions->isFirstSession($appointment->summary))
            ->pluck('suggested_message_code')
            ->filter()
            ->map(fn ($code) => strtoupper($code))
            ->unique()
            ->reject(fn ($code) => in_array($code, $completedCodes, true))
            ->values()
            ->all();
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
