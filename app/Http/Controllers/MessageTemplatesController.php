<?php

namespace App\Http\Controllers;

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

        return view('templates.create', [
            'channel' => $channel,
            'dynamicFields' => MessageTemplate::DYNAMIC_FIELDS,
        ]);
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => ['required', Rule::in(['email', 'sms'])],
            'subject' => 'nullable|required_if:channel,email|string|max:255',
            'body' => 'required|string',
            'is_default' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_default'] = $request->boolean('is_default');

        // If setting as default, unset other defaults for this channel
        if ($validated['is_default']) {
            Auth::user()
                ->messageTemplates()
                ->forChannel($validated['channel'])
                ->update(['is_default' => false]);
        }

        $template = MessageTemplate::create($validated);

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
            'subject' => 'nullable|required_if:channel,email|string|max:255',
            'body' => 'required|string',
            'is_default' => 'boolean',
        ]);

        $validated['is_default'] = $request->boolean('is_default');

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
     * API endpoint for live preview
     */
    public function preview(Request $request)
    {
        $body = $request->input('body', '');
        $subject = $request->input('subject', '');

        $sampleData = [
            'patient_name' => 'María García López',
            'patient_first_name' => 'María',
            'patient_email' => 'maria@ejemplo.com',
            'appointment_date' => 'Lunes 27 de Enero de 2026',
            'appointment_time' => '10:00',
            'appointment_summary' => 'Sesión de terapia',
            'professional_name' => Auth::user()->name ?? 'Dr. Juan Pérez',
            'confirm_link' => url('/link/abc123'),
            'cancel_link' => url('/link/xyz789'),
            'hangout_link' => 'https://meet.google.com/abc-defg-hij',
        ];

        // Parse body
        $parsedBody = $body;
        foreach ($sampleData as $field => $value) {
            $parsedBody = str_replace('{{' . $field . '}}', $value, $parsedBody);
        }

        // Parse subject
        $parsedSubject = $subject;
        foreach ($sampleData as $field => $value) {
            $parsedSubject = str_replace('{{' . $field . '}}', $value, $parsedSubject);
        }

        return response()->json([
            'body' => $parsedBody,
            'subject' => $parsedSubject,
            'charCount' => strlen($parsedBody),
            'smsSegments' => (int) ceil(strlen($parsedBody) / 160),
        ]);
    }
}
