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
            ->where('user_id', auth()->id())
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
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
            'code' => [
                'required', 
                'string', 
                'max:50', 
                Rule::unique('patients')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })
            ],
            'name' => 'required|string|max:255',
            'email' => [
                'required', 
                'email', 
                'max:255', 
                Rule::unique('patients')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })
            ],
            'phone' => 'required|string|max:20',
            'preferred_channel' => 'required|in:email,sms',
            'consent_email' => 'boolean',
            'consent_sms' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'code.unique' => 'Ya existe un paciente con este código.',
            'email.unique' => 'Ya existe un paciente con este email.',
            'code.required' => 'El código del paciente es obligatorio.',
            'name.required' => 'El nombre del paciente es obligatorio.',
            'email.required' => 'El email del paciente es obligatorio.',
            'phone.required' => 'El teléfono del paciente es obligatorio.',
            'preferred_channel.required' => 'Debes seleccionar un canal preferido.',
        ]);

        // Assign user_id
        $validated['user_id'] = auth()->id();
        
        // Normalize code to uppercase
        $validated['code'] = strtoupper($validated['code']);
        
        // Set consent date if any consent is given
        if ($validated['consent_email'] ?? false || 
            $validated['consent_sms'] ?? false) {
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
        // Verify patient belongs to authenticated user
        if ($patient->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para ver este paciente.');
        }
        
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
        // Verify patient belongs to authenticated user
        if ($patient->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para editar este paciente.');
        }
        
        return view('patients.edit', [
            'patient' => $patient,
        ]);
    }

    /**
     * Update the specified patient
     */
    public function update(Request $request, Patient $patient)
    {
        // Verify patient belongs to authenticated user
        if ($patient->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para actualizar este paciente.');
        }
        
        $validated = $request->validate([
            'code' => [
                'required', 
                'string', 
                'max:50', 
                Rule::unique('patients')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($patient->id)
            ],
            'name' => 'required|string|max:255',
            'email' => [
                'required', 
                'email', 
                'max:255', 
                Rule::unique('patients')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($patient->id)
            ],
            'phone' => 'required|string|max:20',
            'preferred_channel' => 'required|in:email,sms',
            'consent_email' => 'boolean',
            'consent_sms' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'code.unique' => 'Ya existe otro paciente con este código.',
            'email.unique' => 'Ya existe otro paciente con este email.',
            'code.required' => 'El código del paciente es obligatorio.',
            'name.required' => 'El nombre del paciente es obligatorio.',
            'email.required' => 'El email del paciente es obligatorio.',
            'phone.required' => 'El teléfono del paciente es obligatorio.',
            'preferred_channel.required' => 'Debes seleccionar un canal preferido.',
        ]);

        // Normalize code to uppercase
        $validated['code'] = strtoupper($validated['code']);
        
        // Update consent date if consent is being given for the first time
        $consentChanged = false;
        if (($validated['consent_email'] ?? false) && !$patient->consent_email) $consentChanged = true;
        if (($validated['consent_sms'] ?? false) && !$patient->consent_sms) $consentChanged = true;
        
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
        // Verify patient belongs to authenticated user
        if ($patient->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para eliminar este paciente.');
        }
        
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
