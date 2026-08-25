<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PatientBulkDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('code');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('preferred_channel')->default('email');
            $table->boolean('consent_email')->default(false);
            $table->boolean('consent_sms')->default(false);
            $table->timestamp('consent_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('google_event_id')->unique();
            $table->string('calendar_id');
            $table->string('summary');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('timezone')->default('Europe/Madrid');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('nimbus_status')->default('pending');
            $table->timestamps();
        });
    }

    public function test_it_deletes_the_selected_patients_without_appointments(): void
    {
        $user = $this->user();
        $kept = $this->patient($user, 'P001');
        $first = $this->patient($user, 'P002');
        $second = $this->patient($user, 'P003');

        $this->actingAs($user)
            ->delete(route('patients.bulk-destroy'), ['ids' => [$first->id, $second->id]])
            ->assertRedirect(route('patients.index'))
            ->assertSessionHas('status', 'Se eliminaron 2 pacientes.');

        $this->assertSame([$kept->id], Patient::pluck('id')->all());
    }

    public function test_it_never_deletes_patients_belonging_to_another_user(): void
    {
        $user = $this->user();
        $intruder = $this->user('otra@ejemplo.com');
        $mine = $this->patient($user, 'P001');
        $theirs = $this->patient($intruder, 'X001');

        $this->actingAs($user)
            ->delete(route('patients.bulk-destroy'), ['ids' => [$mine->id, $theirs->id]])
            ->assertRedirect(route('patients.index'))
            ->assertSessionHas('status', 'Se eliminó 1 paciente.');

        $this->assertNull(Patient::find($mine->id));
        $this->assertNotNull(Patient::find($theirs->id));
    }

    public function test_it_skips_patients_with_appointments_by_default(): void
    {
        $user = $this->user();
        $plain = $this->patient($user, 'P001');
        $booked = $this->patient($user, 'P002');
        $this->appointment($booked);

        $this->actingAs($user)
            ->delete(route('patients.bulk-destroy'), ['ids' => [$plain->id, $booked->id]])
            ->assertSessionHas('status', 'Se eliminó 1 paciente. Se omitió 1 paciente por tener citas asociadas.');

        $this->assertNull(Patient::find($plain->id));
        $this->assertNotNull(Patient::find($booked->id));
    }

    public function test_forcing_deletes_patients_with_appointments_and_orphans_the_appointment(): void
    {
        $user = $this->user();
        $booked = $this->patient($user, 'P002');
        $appointment = $this->appointment($booked);

        $this->actingAs($user)
            ->delete(route('patients.bulk-destroy'), ['ids' => [$booked->id], 'force' => true])
            ->assertSessionHas('status', 'Se eliminó 1 paciente.');

        $this->assertNull(Patient::find($booked->id));
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_it_rejects_an_empty_selection(): void
    {
        $user = $this->user();
        $patient = $this->patient($user, 'P001');

        $this->actingAs($user)
            ->from(route('patients.index'))
            ->delete(route('patients.bulk-destroy'), ['ids' => []])
            ->assertSessionHasErrors('ids');

        $this->assertNotNull(Patient::find($patient->id));
    }

    public function test_guests_cannot_bulk_delete(): void
    {
        $user = $this->user();
        $patient = $this->patient($user, 'P001');

        $this->delete(route('patients.bulk-destroy'), ['ids' => [$patient->id]])
            ->assertRedirect(route('login'));

        $this->assertNotNull(Patient::find($patient->id));
    }

    public function test_purge_deletes_every_patient_of_the_authenticated_user(): void
    {
        $user = $this->user();
        $intruder = $this->user('otra@ejemplo.com');
        $this->patient($user, 'P001');
        $this->patient($user, 'P002');
        $theirs = $this->patient($intruder, 'X001');

        $this->actingAs($user)
            ->delete(route('patients.purge'), ['confirmation' => 'BORRAR'])
            ->assertRedirect(route('patients.index'))
            ->assertSessionHas('status', 'Se eliminaron 2 pacientes.');

        $this->assertSame([$theirs->id], Patient::pluck('id')->all());
    }

    public function test_purge_without_the_confirmation_word_deletes_nothing(): void
    {
        $user = $this->user();
        $this->patient($user, 'P001');

        $this->actingAs($user)
            ->from(route('patients.index'))
            ->delete(route('patients.purge'), ['confirmation' => 'borrame todo'])
            ->assertSessionHasErrors('confirmation');

        $this->assertSame(1, Patient::count());
    }

    public function test_purge_is_case_sensitive(): void
    {
        $user = $this->user();
        $this->patient($user, 'P001');

        $this->actingAs($user)
            ->from(route('patients.index'))
            ->delete(route('patients.purge'), ['confirmation' => 'borrar'])
            ->assertSessionHasErrors('confirmation');

        $this->assertSame(1, Patient::count());
    }

    public function test_purge_skips_patients_with_appointments_unless_forced(): void
    {
        $user = $this->user();
        $this->patient($user, 'P001');
        $booked = $this->patient($user, 'P002');
        $this->appointment($booked);

        $this->actingAs($user)
            ->delete(route('patients.purge'), ['confirmation' => 'BORRAR'])
            ->assertSessionHas('status', 'Se eliminó 1 paciente. Se omitió 1 paciente por tener citas asociadas.');

        $this->assertSame([$booked->id], Patient::pluck('id')->all());

        $this->actingAs($user)
            ->delete(route('patients.purge'), ['confirmation' => 'BORRAR', 'force' => true])
            ->assertSessionHas('status', 'Se eliminó 1 paciente.');

        $this->assertSame(0, Patient::count());
    }

    public function test_purge_ignores_the_active_search_filter(): void
    {
        $user = $this->user();
        $this->patient($user, 'P001');
        $this->patient($user, 'P002');

        $this->actingAs($user)
            ->delete(route('patients.purge') . '?search=P001', ['confirmation' => 'BORRAR'])
            ->assertSessionHas('status', 'Se eliminaron 2 pacientes.');

        $this->assertSame(0, Patient::count());
    }

    public function test_guests_cannot_purge(): void
    {
        $user = $this->user();
        $this->patient($user, 'P001');

        $this->delete(route('patients.purge'), ['confirmation' => 'BORRAR'])
            ->assertRedirect(route('login'));

        $this->assertSame(1, Patient::count());
    }

    public function test_the_listing_renders_the_selection_and_purge_controls(): void
    {
        $user = $this->user();
        $this->patient($user, 'P001');
        $booked = $this->patient($user, 'P002');
        $this->appointment($booked);

        $this->actingAs($user)
            ->get(route('patients.index'))
            ->assertOk()
            ->assertSee('Seleccionar los 2 pacientes de esta página', false)
            ->assertSee('Eliminar seleccionados')
            ->assertSee('Borrar todos mis pacientes')
            ->assertSee('Elimina los 2 pacientes de tu cuenta', false)
            ->assertSee('1 tiene citas asociadas', false);
    }

    public function test_the_listing_hides_the_purge_zone_when_there_are_no_patients(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get(route('patients.index'))
            ->assertOk()
            ->assertDontSee('Borrar todos mis pacientes');
    }

    private function user(string $email = 'psicologa@ejemplo.com'): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Psicóloga',
            'email' => $email,
            'password' => bcrypt('secret'),
            'onboarding_completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function patient(User $user, string $code): Patient
    {
        return Patient::create([
            'user_id' => $user->id,
            'code' => $code,
            'name' => "Paciente {$code}",
            'email' => strtolower($code) . '@ejemplo.com',
        ]);
    }

    private function appointment(Patient $patient): object
    {
        $id = DB::table('appointments')->insertGetId([
            'google_event_id' => 'evt-' . $patient->id,
            'calendar_id' => 'cal-work',
            'summary' => "Sesión {$patient->code}",
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHour(),
            'patient_id' => $patient->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (object) ['id' => $id];
    }
}
