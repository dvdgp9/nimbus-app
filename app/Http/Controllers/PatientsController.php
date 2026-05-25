<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\PatientImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientsController extends Controller
{
    public function __construct(
        private PatientImportService $patientImporter
    ) {}

    /**
     * Show import screen (CSV + paste)
     */
    public function importForm()
    {
        return view('patients.import');
    }

    /**
     * Handle CSV upload import
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

        return redirect()->route('patients.import.form')->with($this->flashFromReport($report));
    }

    /**
     * Handle pasted CSV/TSV import
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

        return redirect()->route('patients.import.form')->with($this->flashFromReport($report));
    }

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
            'status' => $summary,
            'import_duplicates' => $report['duplicates'],
            'import_errors' => $report['ignored'],
        ];
    }

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
     * Accepts prefilled parameters from first session detection
     */
    public function create(Request $request)
    {
        return view('patients.create', [
            'prefill' => [
                'name' => $request->query('name'),
                'email' => $request->query('email'),
                'phone' => $request->query('phone'),
                'notes' => $request->query('notes'),
            ],
        ]);
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
                'nullable',
                'email',
                'max:255',
                Rule::unique('patients')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })
            ],
            'phone' => 'nullable|string|max:20',
            'consent_email' => 'boolean',
            'consent_sms' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'code.unique' => 'Ya existe un paciente con este código.',
            'email.unique' => 'Ya existe un paciente con este email.',
            'code.required' => 'El código del paciente es obligatorio.',
            'name.required' => 'El nombre del paciente es obligatorio.',
        ]);

        // Set default preferred_channel to 'email' for backwards compatibility
        $validated['preferred_channel'] = 'email';

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
                'nullable',
                'email',
                'max:255',
                Rule::unique('patients')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($patient->id)
            ],
            'phone' => 'nullable|string|max:20',
            'consent_email' => 'boolean',
            'consent_sms' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'code.unique' => 'Ya existe otro paciente con este código.',
            'email.unique' => 'Ya existe otro paciente con este email.',
            'code.required' => 'El código del paciente es obligatorio.',
            'name.required' => 'El nombre del paciente es obligatorio.',
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
