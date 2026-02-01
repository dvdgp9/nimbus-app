<x-app-layout>


<div class="page-container">
  {{-- Page Header --}}
  <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
    <div class="page-header mb-0">
      <h1>Citas</h1>
      <p>Próximos 30 días · {{ $appointments->count() }} citas</p>
    </div>
    <div class="flex gap-2">
      <form method="POST" action="{{ route('events.sync') }}" class="inline">
        @csrf
        <input type="hidden" name="account" value="{{ $account }}">
        <button type="submit" class="btn btn-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          <span>Sincronizar</span>
        </button>
      </form>
      <form method="POST" action="{{ route('events.reminders') }}" class="inline">
        @csrf
        <button type="submit" class="btn btn-secondary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span>Enviar recordatorios</span>
        </button>
      </form>
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

  @error('account')
    <div class="alert alert-error mb-6">
      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ $message }}
    </div>
  @enderror

  {{-- Appointments Grid --}}
  @if ($appointments->isEmpty())
    <div class="empty-state">
      <div class="icon">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
      <h3>No hay citas sincronizadas</h3>
      <p>Haz click en "Sincronizar" para importar tus próximas citas de Google Calendar</p>
    </div>
  @else
    <div class="grid grid-cols-1 gap-4">
      @foreach ($appointments as $apt)
        @php
          $patientBelongsToUser = $apt->patient && $apt->patient->user_id === auth()->id();
          $prefillParams = array_filter([
            'code' => $apt->suggested_patient_code,
            'name' => $apt->suggested_patient_name,
          ]);
          $patientCreateUrl = $prefillParams
            ? route('patients.create', $prefillParams)
            : route('patients.create');

          // Border color based on appointment status
          $status = $apt->nimbus_status;
          $borderClass = match ($status) {
              'confirmed' => 'border-emerald-400/60',   // verde
              'cancelled', 'cancelled_acknowledged' => 'border-red-400/60',       // rojo
              'reminder_sent' => 'border-amber-400/60', // amarillo (recordatorio enviado)
              'pending' => 'border-slate-300/40',       // gris claro (pendiente)
              default => 'border-slate-500/40',         // neutro para otros estados
          };
        @endphp
        <div class="event-card border-2 {{ $borderClass }}">
          {{-- Patient Status Badge --}}
          @if (!$apt->patient)
            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs sm:text-sm">
              <div class="inline-flex items-center gap-1 text-yellow-400 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Sin paciente asignado</span>
              </div>

              @if ($apt->suggested_patient_code || $apt->suggested_patient_name)
                <span class="inline-flex items-center gap-1 rounded-full bg-white/5 px-2 py-1 text-white/70">
                  @if ($apt->suggested_patient_code)
                    <span><strong>{{ $apt->suggested_patient_code }}</strong></span>
                  @endif
                  @if ($apt->suggested_patient_name)
                    <span>- {{ $apt->suggested_patient_name }}</span>
                  @endif
                </span>
              @endif

              <a href="{{ $patientCreateUrl }}" class="btn btn-secondary btn-xs">
                Crear paciente
              </a>
            </div>
          @elseif (!$patientBelongsToUser)
            <div class="mb-3 flex items-center gap-2 text-yellow-400 text-sm font-medium">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
              <span>Sin paciente asignado</span>
            </div>
          @endif

          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h3>{{ $apt->summary ?? '(Sin título)' }}</h3>
              <div class="meta">
                <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{ $apt->calendar_id }}
              </div>
              
              {{-- Patient Info --}}
              @if ($apt->patient)
                <div class="mt-2 text-sm {{ $patientBelongsToUser ? 'text-green-400' : 'text-yellow-400' }} flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                  <strong>{{ $apt->patient->code }}</strong> - {{ $apt->patient->name }}
                </div>
              @endif
            </div>
            @if ($apt->hangout_link)
              <a href="{{ $apt->hangout_link }}" target="_blank" class="btn btn-primary text-xs py-1 px-3">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Unirse
              </a>
            @endif
          </div>
          
          <dl>
            <div>
              <dt>Inicio</dt>
              <dd>{{ $apt->formatted_date }} - {{ $apt->formatted_time }}</dd>
            </div>
            <div>
              <dt>Estado</dt>
              <dd>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                  {{ $apt->nimbus_status === 'confirmed' ? 'bg-green-500/20 text-green-400' : '' }}
                  {{ in_array($apt->nimbus_status, ['cancelled', 'cancelled_acknowledged']) ? 'bg-red-500/20 text-red-400' : '' }}
                  {{ $apt->nimbus_status === 'reminder_sent' ? 'bg-blue-500/20 text-blue-400' : '' }}
                  {{ $apt->nimbus_status === 'pending' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                  {{ $apt->nimbus_status === 'cancelled_acknowledged' ? 'Cancelled' : ucfirst($apt->nimbus_status) }}
                </span>
              </dd>
            </div>
            <div>
              <dt>Código</dt>
              <dd>
                @if($apt->message_code)
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-500/20 text-purple-400">
                    {{ $apt->message_code }}
                  </span>
                @else
                  <span class="text-yellow-400 text-xs">Sin código</span>
                @endif
              </dd>
            </div>
            <div>
              <dt>Recordatorio</dt>
              <dd>{{ $apt->reminder_sent_at ? $apt->reminder_sent_at->diffForHumans() : '—' }}</dd>
            </div>
          </dl>

          @if ($apt->description)
            <details class="mt-4">
              <summary class="cursor-pointer text-sm text-white/60 hover:text-white/80 transition select-none">
                Ver descripción
              </summary>
              <div class="mt-3 text-sm text-white/70 bg-white/5 rounded-lg p-3">
                <pre class="whitespace-pre-wrap font-sans">{{ $apt->description }}</pre>
              </div>
            </details>
          @endif
        </div>
      @endforeach
    </div>
  @endif
</div>
</x-app-layout>
