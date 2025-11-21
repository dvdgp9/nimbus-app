<x-app-layout>


<div class="page-container max-w-5xl">
  {{-- Page Header --}}
  <div class="mb-8">
    <a href="{{ route('patients.index') }}" class="text-cyan-400 hover:text-cyan-300 transition inline-flex items-center gap-1 mb-4">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
      </svg>
      Volver a pacientes
    </a>
    <div class="flex items-start justify-between gap-4">
      <div class="page-header mb-0">
        <div class="flex items-center gap-3 mb-2">
          <span class="font-mono text-2xl font-bold text-cyan-400">{{ $patient->code }}</span>
          <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium
            {{ $patient->preferred_channel === 'email' ? 'bg-blue-500/20 text-blue-400' : '' }}
            {{ $patient->preferred_channel === 'sms' ? 'bg-green-500/20 text-green-400' : '' }}
            {{ $patient->preferred_channel === 'whatsapp' ? 'bg-emerald-500/20 text-emerald-400' : '' }}">
            {{ ucfirst($patient->preferred_channel) }}
          </span>
        </div>
        <h1>{{ $patient->name }}</h1>
      </div>
      <div class="flex gap-2">
        <a href="{{ route('patients.edit', $patient) }}" class="btn bg-white/5 hover:bg-white/10 text-white">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
          </svg>
          Editar
        </a>
        <form method="POST" action="{{ route('patients.destroy', $patient) }}" onsubmit="return confirm('¿Estás seguro de eliminar este paciente? Esta acción no se puede deshacer.');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn bg-red-500/10 hover:bg-red-500/20 text-red-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Eliminar
          </button>
        </form>
      </div>
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

  @error('delete')
    <div class="alert alert-error mb-6">
      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ $message }}
    </div>
  @enderror

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Patient Info --}}
    <div class="lg:col-span-1 space-y-6">
      {{-- Contact Info --}}
      <div class="bg-white/5 rounded-xl border border-white/10 p-6">
        <h2 class="text-lg font-semibold text-white mb-4">Datos de contacto</h2>
        <dl class="space-y-3">
          <div>
            <dt class="text-sm text-white/60 mb-1">Email</dt>
            <dd class="text-white/90 font-medium">{{ $patient->email ?: '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm text-white/60 mb-1">Teléfono</dt>
            <dd class="text-white/90 font-medium">{{ $patient->phone ?: '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm text-white/60 mb-1">Canal preferido</dt>
            <dd>
              <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                {{ $patient->preferred_channel === 'email' ? 'bg-blue-500/20 text-blue-300' : '' }}
                {{ $patient->preferred_channel === 'sms' ? 'bg-green-500/20 text-green-300' : '' }}
                {{ $patient->preferred_channel === 'whatsapp' ? 'bg-emerald-500/20 text-emerald-300' : '' }}">
                {{ ucfirst($patient->preferred_channel) }}
              </span>
            </dd>
          </div>
        </dl>
      </div>

      {{-- Consents --}}
      <div class="bg-white/5 rounded-xl border border-white/10 p-6">
        <h2 class="text-lg font-semibold text-white mb-4">Consentimientos</h2>
        @if($patient->consent_date)
          <p class="text-xs text-white/60 mb-3">Consentimiento dado el {{ $patient->consent_date->format('d/m/Y') }}</p>
        @endif
        <div class="space-y-2">
          <div class="flex items-center gap-2">
            @if($patient->consent_email)
              <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-white">Email</span>
            @else
              <svg class="w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-white/40">Email</span>
            @endif
          </div>
          <div class="flex items-center gap-2">
            @if($patient->consent_sms)
              <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-white">SMS</span>
            @else
              <svg class="w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-white/40">SMS</span>
            @endif
          </div>
          <div class="flex items-center gap-2">
            @if($patient->consent_whatsapp)
              <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-white">WhatsApp</span>
            @else
              <svg class="w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-white/40">WhatsApp</span>
            @endif
          </div>
        </div>
      </div>

      {{-- Notes --}}
      @if($patient->notes)
        <div class="bg-white/5 rounded-xl border border-white/10 p-6">
          <h2 class="text-lg font-semibold text-white mb-3">Notas</h2>
          <p class="text-white/70 text-sm whitespace-pre-wrap">{{ $patient->notes }}</p>
        </div>
      @endif
    </div>

    {{-- Appointments --}}
    <div class="lg:col-span-2">
      <div class="bg-white/5 rounded-xl border border-white/10 p-6">
        <h2 class="text-lg font-semibold text-white mb-4">
          Citas ({{ $patient->appointments->count() }})
        </h2>

        @if($patient->appointments->isEmpty())
          <div class="text-center py-12">
            <svg class="w-12 h-12 text-white/20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="text-white/60">Este paciente no tiene citas registradas</p>
          </div>
        @else
          <div class="space-y-3">
            @foreach($patient->appointments as $apt)
              <div class="bg-white/5 rounded-lg p-4 border border-white/10 hover:border-white/20 transition">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1">
                    <h3 class="text-white font-medium mb-1">{{ $apt->summary }}</h3>
                    <div class="text-sm text-white/70 space-y-1">
                      <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>{{ $apt->formatted_date }} - {{ $apt->formatted_time }}</span>
                      </div>
                      @if($apt->description)
                        <p class="text-white/50 text-xs mt-2">{{ Str::limit($apt->description, 100) }}</p>
                      @endif
                    </div>
                  </div>
                  <div class="flex flex-col items-end gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                      {{ $apt->nimbus_status === 'confirmed' ? 'bg-green-500/20 text-green-300' : '' }}
                      {{ $apt->nimbus_status === 'cancelled' ? 'bg-red-500/20 text-red-300' : '' }}
                      {{ $apt->nimbus_status === 'reminder_sent' ? 'bg-blue-500/20 text-blue-300' : '' }}
                      {{ $apt->nimbus_status === 'pending' ? 'bg-slate-500/20 text-slate-300' : '' }}">
                      {{ ucfirst($apt->nimbus_status) }}
                    </span>
                    @if($apt->hangout_link)
                      <a href="{{ $apt->hangout_link }}" target="_blank" class="text-cyan-400 hover:text-cyan-300 text-xs">
                        Ver enlace Meet →
                      </a>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
</x-app-layout>
