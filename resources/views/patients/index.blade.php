<x-app-layout>


<div class="page-container">
  {{-- Page Header --}}
  <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
    <div class="page-header mb-0">
      <h1>Pacientes</h1>
      <p>Gestiona tus pacientes y sus datos de contacto</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('patients.import.form') }}" class="btn bg-white/5 hover:bg-white/10 text-white">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
        </svg>
        <span>Importar CSV</span>
      </a>
      <a href="{{ route('patients.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>Nuevo paciente</span>
      </a>
    </div>
  </div>

  {{-- Alerts --}}
  @if (session('status'))
    <div class="alert alert-success mb-6">
      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ session('status') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-error mb-6">
      <ul class="list-disc list-inside space-y-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Search Bar --}}
  <div class="mb-6">
    <form method="GET" action="{{ route('patients.index') }}" class="flex gap-2">
      <div class="flex-1">
        <input 
          type="text" 
          name="search" 
          value="{{ $search }}"
          placeholder="Buscar por código, nombre, email o teléfono..."
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition"
        >
      </div>
      <button type="submit" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        Buscar
      </button>
      @if($search)
        <a href="{{ route('patients.index') }}" class="btn bg-white/5 hover:bg-white/10 text-white">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          Limpiar
        </a>
      @endif
    </form>
  </div>

  {{-- Patients List --}}
  @if ($patients->isEmpty())
    <div class="empty-state">
      <div class="icon">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
      </div>
      <h3>{{ $search ? 'No se encontraron pacientes' : 'No hay pacientes registrados' }}</h3>
      <p>{{ $search ? 'Intenta con otros términos de búsqueda' : 'Crea tu primer paciente para empezar' }}</p>
      @if(!$search)
        <a href="{{ route('patients.create') }}" class="btn btn-primary mt-4 inline-flex">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Crear primer paciente
        </a>
      @endif
    </div>
  @else
    <form method="POST" action="{{ route('patients.bulk-destroy') }}" id="bulk-form">
      @csrf
      @method('DELETE')
      <input type="hidden" name="force" id="bulk-force" value="0">

      {{-- Bulk actions bar, revealed once something is selected --}}
      <div class="bulk-bar" id="bulk-bar" hidden>
        <span class="bulk-bar-count" id="bulk-count"></span>
        <span class="bulk-bar-note" id="bulk-note" hidden></span>
        <div class="bulk-bar-actions">
          <button type="button" class="btn-quiet" id="bulk-clear">Deseleccionar</button>
          <button type="submit" class="btn-danger" id="bulk-submit">Eliminar seleccionados</button>
        </div>
      </div>

      <div class="select-all-row">
        <input type="checkbox" id="select-page" class="patient-checkbox" style="margin-top:0">
        <label for="select-page">Seleccionar los {{ $patients->count() }} pacientes de esta página</label>
      </div>

    <div class="grid grid-cols-1 gap-3">
      @foreach($patients as $patient)
        <div class="bg-white/5 rounded-xl border border-white/10 p-4 hover:bg-white/[0.07] transition">
          <div class="flex items-start justify-between gap-4">
            <input
              type="checkbox"
              name="ids[]"
              value="{{ $patient->id }}"
              class="patient-checkbox js-patient-checkbox"
              data-appointments="{{ $patient->appointments_count }}"
              aria-label="Seleccionar {{ $patient->name }}"
            >
            {{-- Left: Name and Code --}}
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="text-white font-semibold text-base">{{ $patient->name }}</h3>
                <span class="text-white/40">-</span>
                <span class="font-mono text-base font-bold text-cyan-300">{{ $patient->code }}</span>
              </div>
              <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                  {{ $patient->preferred_channel === 'email' ? 'bg-blue-500/20 text-blue-300' : '' }}
                  {{ $patient->preferred_channel === 'sms' ? 'bg-green-500/20 text-green-300' : '' }}">
                  {{ ucfirst($patient->preferred_channel) }}
                </span>
              </div>
              <div class="space-y-0.5 text-[13px]">
                @if($patient->email)
                  <div class="flex items-center gap-2 text-white/80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    {{ $patient->email }}
                  </div>
                @endif
                @if($patient->phone)
                  <div class="flex items-center gap-2 text-white/80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    {{ $patient->phone }}
                  </div>
                @endif
                @if(!$patient->email && !$patient->phone)
                  <span class="text-white/40">Sin datos de contacto</span>
                @endif
              </div>
            </div>

            {{-- Center: Consents and Stats --}}
            <div class="flex flex-col items-center gap-2">
              <div class="text-center">
                <div class="text-xl font-bold text-white">{{ $patient->appointments_count }}</div>
                <div class="text-[11px] text-white/60">{{ $patient->appointments_count === 1 ? 'Cita' : 'Citas' }}</div>
              </div>
              <div class="flex gap-1">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded {{ $patient->consent_email ? 'bg-green-500/20 text-green-300' : 'bg-white/5 text-white/30' }}" title="Email">
                  @if($patient->consent_email)
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  @else
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                  @endif
                </span>
                <span class="inline-flex items-center justify-center w-6 h-6 rounded {{ $patient->consent_sms ? 'bg-green-500/20 text-green-300' : 'bg-white/5 text-white/30' }}" title="SMS">
                  @if($patient->consent_sms)
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  @else
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                  @endif
                </span>
              </div>
            </div>

            {{-- Right: Actions --}}
            <div class="flex flex-col gap-1.5">
              <a href="{{ route('patients.show', $patient) }}" class="inline-flex items-center justify-center px-3 py-1.5 bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-300 hover:text-cyan-200 rounded-lg transition text-xs font-medium">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Ver
              </a>
              <a href="{{ route('patients.edit', $patient) }}" class="inline-flex items-center justify-center px-3 py-1.5 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-lg transition text-xs font-medium">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar
              </a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
    </form>

    {{-- Pagination --}}
    @if($patients->hasPages())
      <div class="mt-6">
        {{ $patients->links() }}
      </div>
    @endif
  @endif

  {{-- Danger zone: wipe every patient of this account --}}
  @if($totalPatients > 0)
    <div class="danger-zone">
      <h3>Borrar todos mis pacientes</h3>
      <p>
        Elimina los {{ $totalPatients }} pacientes de tu cuenta, no solo los de esta página ni los del filtro de búsqueda.
        La acción es permanente: no hay papelera.
        @if($totalWithAppointments > 0)
          {{ $totalWithAppointments }} {{ $totalWithAppointments === 1 ? 'tiene' : 'tienen' }} citas asociadas.
        @endif
      </p>

      <button type="button" class="btn-danger mt-3" id="purge-open">Borrar todos mis pacientes</button>

      <form method="POST" action="{{ route('patients.purge') }}" class="purge-dialog" id="purge-dialog" hidden>
        @csrf
        @method('DELETE')

        @if($totalWithAppointments > 0)
          <div class="purge-checkbox-row">
            <input type="checkbox" name="force" value="1" id="purge-force" class="patient-checkbox" style="margin-top:2px">
            <label for="purge-force" style="margin:0">
              Borrar también los {{ $totalWithAppointments }} pacientes con citas asociadas.
              Sus citas no se borran: quedan sin paciente asignado.
            </label>
          </div>
        @endif

        <div>
          <label for="purge-confirmation">Escribe <span class="font-mono font-bold text-red-300">{{ $purgeConfirmation }}</span> para confirmar</label>
          <input type="text" name="confirmation" id="purge-confirmation" autocomplete="off" placeholder="{{ $purgeConfirmation }}">
        </div>

        <div class="flex items-center gap-2">
          <button type="submit" class="btn-danger">Borrar definitivamente</button>
          <button type="button" class="btn-quiet" id="purge-cancel">Cancelar</button>
        </div>
      </form>
    </div>
  @endif
</div>

<script>
  (function () {
    var form = document.getElementById('bulk-form');

    if (form) {
      var boxes = Array.prototype.slice.call(document.querySelectorAll('.js-patient-checkbox'));
      var bar = document.getElementById('bulk-bar');
      var count = document.getElementById('bulk-count');
      var note = document.getElementById('bulk-note');
      var selectPage = document.getElementById('select-page');
      var force = document.getElementById('bulk-force');

      var selected = function () {
        return boxes.filter(function (box) { return box.checked; });
      };

      var withAppointments = function () {
        return selected().filter(function (box) {
          return parseInt(box.dataset.appointments, 10) > 0;
        });
      };

      var refresh = function () {
        var chosen = selected();
        var booked = withAppointments();

        bar.hidden = chosen.length === 0;
        count.textContent = chosen.length === 1
          ? '1 paciente seleccionado'
          : chosen.length + ' pacientes seleccionados';

        note.hidden = booked.length === 0;
        note.textContent = booked.length === 1
          ? '1 de ellos tiene citas'
          : booked.length + ' de ellos tienen citas';

        selectPage.checked = boxes.length > 0 && chosen.length === boxes.length;
      };

      boxes.forEach(function (box) { box.addEventListener('change', refresh); });

      selectPage.addEventListener('change', function () {
        boxes.forEach(function (box) { box.checked = selectPage.checked; });
        refresh();
      });

      document.getElementById('bulk-clear').addEventListener('click', function () {
        boxes.forEach(function (box) { box.checked = false; });
        refresh();
      });

      form.addEventListener('submit', function (event) {
        var chosen = selected();
        var booked = withAppointments();

        if (chosen.length === 0) {
          event.preventDefault();
          return;
        }

        var message = 'Vas a eliminar ' + chosen.length + ' paciente(s). Esta accion no se puede deshacer.';

        if (booked.length > 0) {
          message += '\n\n' + booked.length + ' de ellos tienen citas asociadas.'
            + '\n\nAceptar: borrarlos tambien (sus citas quedan sin paciente asignado).'
            + '\nCancelar: no borrar nada.';

          if (!window.confirm(message)) {
            event.preventDefault();
            return;
          }

          force.value = '1';
          return;
        }

        if (!window.confirm(message)) {
          event.preventDefault();
        }
      });

      refresh();
    }

    var purgeOpen = document.getElementById('purge-open');

    if (purgeOpen) {
      var dialog = document.getElementById('purge-dialog');

      purgeOpen.addEventListener('click', function () {
        dialog.hidden = false;
        purgeOpen.hidden = true;
        document.getElementById('purge-confirmation').focus();
      });

      document.getElementById('purge-cancel').addEventListener('click', function () {
        dialog.hidden = true;
        purgeOpen.hidden = false;
      });

      dialog.addEventListener('submit', function (event) {
        if (!window.confirm('Ultima confirmacion: se borraran todos tus pacientes de forma permanente.')) {
          event.preventDefault();
        }
      });
    }
  })();
</script>
</x-app-layout>
