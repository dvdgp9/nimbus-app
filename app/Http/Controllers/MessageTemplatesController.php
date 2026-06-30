<?php

namespace App\Http\Controllers;

use App\Services\RescheduleLinkService;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MessageTemplatesController extends Controller
{
    /**
     * Display a listing of templates
     */
    public function index(Request $request)
    {
        $channel = $request->get('channel', 'email');
        
        $templates = Auth::user()
            ->messageTemplates()
            ->forChannel($channel)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return view('templates.index', [
            'templates' => $templates,
            'channel' => $channel,
            'dynamicFields' => MessageTemplate::DYNAMIC_FIELDS,
        ]);
    }

    /**
     * Show the form for creating a new template
     */
    public function create(Request $request)
    {
        $channel = $request->get('channel', 'email');

        // Default values defined here to avoid Blade parsing issues with {{ }}
        $defaultSubject = 'Recordatorio de tu cita del {{appointment_date}}';
        $defaultBodySms = 'Hola {{patient_first_name}}, te recuerdo tu cita del {{appointment_date}} a las {{appointment_time}}. Confirmar: {{confirm_link}} · Cancelar: {{cancel_link}}';
        $defaultBodyEmail = "Hola {{patient_first_name}},\n\nTe escribo para recordarte tu próxima sesión: el {{appointment_date}} a las {{appointment_time}}.\n\nSi todo sigue igual, confirma con un clic. Si no puedes asistir, avísame también con un clic y reorganizamos.\n\n[BOTON_CONFIRMAR]\n\n[BOTON_CANCELAR]\n\nUn abrazo,\n{{professional_name}}";

        return view('templates.create', [
            'channel' => $channel,
            'dynamicFields' => MessageTemplate::DYNAMIC_FIELDS,
            'defaultSubject' => $defaultSubject,
            'defaultBody' => $channel === 'sms' ? $defaultBodySms : $defaultBodyEmail,
        ]);
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:20',
                'alpha_num',
                Rule::unique('message_templates')->where(function ($query) use ($request) {
                    return $query->where('user_id', Auth::id())
                                 ->where('channel', $request->channel);
                }),
            ],
            'channel' => ['required', Rule::in(['email', 'sms'])],
            'subject' => 'nullable|required_if:channel,email|string|max:255',
            'body' => 'required|string',
            'is_default' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_default'] = $request->boolean('is_default');
        // Normalize code to uppercase
        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        // If setting as default, unset other defaults for this channel
        if ($validated['is_default']) {
            Auth::user()
                ->messageTemplates()
                ->forChannel($validated['channel'])
                ->update(['is_default' => false]);
        }

        $template = MessageTemplate::create($validated);

        if ($request->boolean('from_onboarding')) {
            return redirect()
                ->route('onboarding.step', ['step' => 4])
                ->with('status', 'Plantilla creada correctamente');
        }

        return redirect()
            ->route('templates.index', ['channel' => $template->channel])
            ->with('status', 'Plantilla creada correctamente');
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(MessageTemplate $template)
    {
        // Authorization check
        if ($template->user_id !== Auth::id()) {
            abort(403);
        }

        return view('templates.edit', [
            'template' => $template,
            'dynamicFields' => MessageTemplate::DYNAMIC_FIELDS,
        ]);
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, MessageTemplate $template)
    {
        // Authorization check
        if ($template->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:20',
                'alpha_num',
                Rule::unique('message_templates')->where(function ($query) use ($template) {
                    return $query->where('user_id', Auth::id())
                                 ->where('channel', $template->channel);
                })->ignore($template->id),
            ],
            'subject' => 'nullable|required_if:channel,email|string|max:255',
            'body' => 'required|string',
            'is_default' => 'boolean',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        // Normalize code to uppercase
        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        // If setting as default, unset other defaults for this channel
        if ($validated['is_default'] && !$template->is_default) {
            Auth::user()
                ->messageTemplates()
                ->forChannel($template->channel)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update($validated);

        return redirect()
            ->route('templates.index', ['channel' => $template->channel])
            ->with('status', 'Plantilla actualizada correctamente');
    }

    /**
     * Remove the specified template
     */
    public function destroy(MessageTemplate $template)
    {
        // Authorization check
        if ($template->user_id !== Auth::id()) {
            abort(403);
        }

        $channel = $template->channel;
        $template->delete();

        return redirect()
            ->route('templates.index', ['channel' => $channel])
            ->with('status', 'Plantilla eliminada correctamente');
    }

    /**
     * Duplicate an existing template
     */
    public function duplicate(MessageTemplate $template)
    {
        // Authorization check
        if ($template->user_id !== Auth::id()) {
            abort(403);
        }

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (copia)';
        $newTemplate->is_default = false;
        $newTemplate->save();

        return redirect()
            ->route('templates.edit', $newTemplate)
            ->with('status', 'Plantilla duplicada correctamente');
    }

    /**
     * Set a template as default
     */
    public function setDefault(MessageTemplate $template)
    {
        // Authorization check
        if ($template->user_id !== Auth::id()) {
            abort(403);
        }

        // Unset other defaults for this channel
        Auth::user()
            ->messageTemplates()
            ->forChannel($template->channel)
            ->update(['is_default' => false]);

        // Set this one as default
        $template->update(['is_default' => true]);

        return redirect()
            ->route('templates.index', ['channel' => $template->channel])
            ->with('status', 'Plantilla establecida como predeterminada');
    }

    /**
     * API endpoint for live preview.
     * For email: renders the actual templated-reminder Blade view as HTML.
     * For SMS: returns parsed plain text with character/segment counts.
     */
    public function preview(Request $request)
    {
        $body = (string) $request->input('body', '');
        $subject = (string) $request->input('subject', '');
        $channel = $request->input('channel', 'email');

        $sample = $this->samplePreviewData();

        $parsedBody = $this->applyVariables($body, $sample['fields']);
        $parsedSubject = $this->applyVariables($subject, $sample['fields']);

        if ($channel === 'sms') {
            return response()->json([
                'channel' => 'sms',
                'body' => $parsedBody,
                'subject' => $parsedSubject,
                'charCount' => mb_strlen($parsedBody),
                'smsSegments' => max(1, (int) ceil(mb_strlen($parsedBody) / 160)),
            ]);
        }

        // Render the actual email Blade view so the preview matches what the patient receives.
        $html = view('emails.templated-reminder', [
            'appointment' => $sample['appointment'],
            'patient' => $sample['patient'],
            'emailBody' => $parsedBody,
            'confirmUrl' => $sample['fields']['confirm_link'],
            'cancelUrl' => $sample['fields']['cancel_link'],
            'rescheduleUrl' => $sample['fields']['reschedule_link'],
        ])->render();

        return response()->json([
            'channel' => 'email',
            'subject' => $parsedSubject,
            'html' => $html,
        ]);
    }

    /**
     * Send a test email to the authenticated user with sample data.
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $user = Auth::user();
        if (empty($user->email)) {
            return response()->json(['error' => 'Tu cuenta no tiene un email asociado.'], 422);
        }

        $sample = $this->samplePreviewData();
        $parsedBody = $this->applyVariables($request->input('body'), $sample['fields']);
        $parsedSubject = $this->applyVariables($request->input('subject'), $sample['fields']);

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\TemplatedReminder(
                    $sample['appointment'],
                    $sample['patient'],
                    '[Prueba] ' . $parsedSubject,
                    $parsedBody,
                    [
                        'confirmUrl' => $sample['fields']['confirm_link'],
                        'cancelUrl' => $sample['fields']['cancel_link'],
                        'rescheduleUrl' => $sample['fields']['reschedule_link'],
                    ]
                )
            );

            return response()->json([
                'ok' => true,
                'message' => 'Correo de prueba enviado a ' . $user->email,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'No se pudo enviar el correo de prueba: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sample data used in preview & test send. Uses an in-memory Appointment and Patient
     * (not persisted) so the preview matches the real email rendering pipeline.
     */
    protected function samplePreviewData(): array
    {
        $user = Auth::user();
        $professionalName = $user->name ?? 'Dra. Lucía Hernández';

        $patient = new \App\Models\Patient([
            'name' => 'María García López',
            'email' => $user->email ?? 'maria@ejemplo.com',
            'phone' => '+34 600 123 456',
            'code' => 'MGL',
        ]);
        $patient->setRelation('user', $user);

        $appointment = new \App\Models\Appointment([
            'summary' => 'Sesión de terapia',
            'start_at' => now()->next(\Carbon\Carbon::MONDAY)->setTime(10, 0),
            'end_at' => now()->next(\Carbon\Carbon::MONDAY)->setTime(11, 0),
            'timezone' => config('app.timezone', 'Europe/Madrid'),
            'hangout_link' => null,
            'description' => null,
        ]);
        $appointment->setRelation('patient', $patient);

        $fields = [
            'patient_name' => $patient->name,
            'patient_first_name' => 'María',
            'patient_email' => $patient->email,
            'appointment_date' => $appointment->formatted_date,
            'appointment_time' => $appointment->formatted_time,
            'appointment_summary' => $appointment->summary,
            'professional_name' => $professionalName,
            'confirm_link' => url('/link/preview-confirm'),
            'cancel_link' => url('/link/preview-cancel'),
            'reschedule_link' => RescheduleLinkService::forAppointment($appointment),
            'hangout_link' => '',
        ];

        return compact('appointment', 'patient', 'fields');
    }

    protected function applyVariables(string $text, array $fields): string
    {
        foreach ($fields as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }
        return $text;
    }
}
