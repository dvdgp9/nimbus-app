<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientsController extends Controller
{
    /**
     * Display a listing of patients with search
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        $patients = Patient::query()
            ->when($search, function ($query, $search) {
                $query->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->withCount('appointments')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('patients.index', [
            'patients' => $patients,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new patient
     */
    public function create()
    {
        return view('patients.create');
    }

    /**
     * Store a newly created patient
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:patients,code',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:patients,email',
            'phone' => 'nullable|string|max:20',
            'preferred_channel' => 'required|in:email,sms,whatsapp',
            'consent_email' => 'boolean',
            'consent_sms' => 'boolean',
            'consent_whatsapp' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'code.unique' => 'Ya existe un paciente con este código.',
            'email.unique' => 'Ya existe un paciente con este email.',
            'code.required' => 'El código del paciente es obligatorio.',
            'name.required' => 'El nombre del paciente es obligatorio.',
            'preferred_channel.required' => 'Debes seleccionar un canal preferido.',
        ]);

        // Assign user_id
        $validated['user_id'] = auth()->id();
        
        // Normalize code to uppercase
        $validated['code'] = strtoupper($validated['code']);
        
        // Set consent date if any consent is given
        if ($validated['consent_email'] ?? false || 
            $validated['consent_sms'] ?? false || 
            $validated['consent_whatsapp'] ?? false) {
            $validated['consent_date'] = now();
        }

        $patient = Patient::create($validated);

        return redirect()->route('patients.show', $patient)
            ->with('status', 'Paciente creado exitosamente');
    }

    /**
     * Display the specified patient with appointments
     */
    public function show(Patient $patient)
    {
        $patient->load(['appointments' => function ($query) {
            $query->orderBy('start_at', 'desc');
        }]);

        return view('patients.show', [
            'patient' => $patient,
        ]);
    }

    /**
     * Show the form for editing the specified patient
     */
    public function edit(Patient $patient)
    {
        return view('patients.edit', [
            'patient' => $patient,
        ]);
    }

    /**
     * Update the specified patient
     */
    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('patients')->ignore($patient->id)],
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('patients')->ignore($patient->id)],
            'phone' => 'nullable|string|max:20',
            'preferred_channel' => 'required|in:email,sms,whatsapp',
            'consent_email' => 'boolean',
            'consent_sms' => 'boolean',
            'consent_whatsapp' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'code.unique' => 'Ya existe otro paciente con este código.',
            'email.unique' => 'Ya existe otro paciente con este email.',
            'code.required' => 'El código del paciente es obligatorio.',
            'name.required' => 'El nombre del paciente es obligatorio.',
            'preferred_channel.required' => 'Debes seleccionar un canal preferido.',
        ]);

        // Normalize code to uppercase
        $validated['code'] = strtoupper($validated['code']);
        
        // Update consent date if consent is being given for the first time
        $consentChanged = false;
        if (($validated['consent_email'] ?? false) && !$patient->consent_email) $consentChanged = true;
        if (($validated['consent_sms'] ?? false) && !$patient->consent_sms) $consentChanged = true;
        if (($validated['consent_whatsapp'] ?? false) && !$patient->consent_whatsapp) $consentChanged = true;
        
        if ($consentChanged && !$patient->consent_date) {
            $validated['consent_date'] = now();
        }

        $patient->update($validated);

        return redirect()->route('patients.show', $patient)
            ->with('status', 'Paciente actualizado exitosamente');
    }

    /**
     * Remove the specified patient
     */
    public function destroy(Patient $patient)
    {
        $appointmentsCount = $patient->appointments()->count();
        
        if ($appointmentsCount > 0) {
            return back()->withErrors([
                'delete' => "No se puede eliminar el paciente porque tiene {$appointmentsCount} cita(s) asociada(s). Elimina o reasigna las citas primero."
            ]);
        }

        $patient->delete();

        return redirect()->route('patients.index')
            ->with('status', 'Paciente eliminado exitosamente');
    }
}
