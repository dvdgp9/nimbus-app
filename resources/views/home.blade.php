<x-app-layout>
<div class="page-container">
  {{-- Page Header --}}
  <div class="page-header">
    <h1>Panel de control</h1>
    <p>Gestiona tus calendarios, mensajes y recordatorios automáticos</p>
  </div>

  {{-- Connection Status Banner --}}
  @if(!$isConnected)
  <div class="mb-6 bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 flex items-center gap-4">
    <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center flex-shrink-0">
      <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
      </svg>
    </div>
    <div class="flex-1">
      <h3 class="text-amber-300 font-semibold">Conecta tu calendario</h3>
      <p class="text-amber-200/70 text-sm">Para empezar a enviar recordatorios, conecta tu cuenta de Google Calendar</p>
    </div>
    <a href="{{ route('google.connect') }}" class="btn btn-primary flex-shrink-0">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
      </svg>
      Conectar Google
    </a>
  </div>
  @endif

  {{-- Stats Grid --}}
  <div class="stats-grid">
    <div class="stat-card {{ $isConnected ? 'border-green-500/30' : 'border-white/10' }}">
      <div class="flex items-center gap-2 mb-1">
        @if($isConnected)
          <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        @else
          <svg class="w-4 h-4 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        @endif
        <div class="label">Calendario</div>
      </div>
      <div class="value {{ $isConnected ? 'text-green-400' : '' }}">{{ $isConnected ? 'Conectado' : 'Sin conectar' }}</div>
    </div>
    <div class="stat-card">
      <div class="label">Enviados hoy</div>
      <div class="value">{{ $remindersSentToday }}</div>
    </div>
    <div class="stat-card">
      <div class="label">Confirmados (sem)</div>
      <div class="value">{{ $confirmedThisWeek }}</div>
    </div>
    <div class="stat-card">
      <div class="label">Pacientes</div>
      <div class="value">{{ $patientsCount }}</div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    {{-- Upcoming Appointments --}}
    <div class="lg:col-span-2 bg-white/5 rounded-xl border border-white/10 p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          Próximas citas
        </h2>
        <a href="{{ route('events.index', ['account' => $account]) }}" class="text-cyan-400 hover:text-cyan-300 text-sm font-medium transition">
          Ver todas ({{ $upcomingCount }}) →
        </a>
      </div>

      @if($upcomingAppointments->isEmpty())
        <div class="text-center py-8 text-white/40">
          <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <p>No hay citas en las próximas 48 horas</p>
        </div>
      @else
        <div class="space-y-3">
          @foreach($upcomingAppointments as $apt)
            <div class="flex items-center gap-4 p-3 bg-white/5 rounded-lg hover:bg-white/[0.07] transition">
              {{-- Time --}}
              <div class="text-center min-w-[60px]">
                <div class="text-xs text-white/50">
                  {{ $apt->start_at->isToday() ? 'Hoy' : ($apt->start_at->isTomorrow() ? 'Mañana' : $apt->start_at->format('d/m')) }}
                </div>
                <div class="text-lg font-bold text-white">{{ $apt->start_at->format('H:i') }}</div>
              </div>

              {{-- Divider --}}
              <div class="w-px h-10 bg-white/10"></div>

              {{-- Info --}}
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  @if($apt->patient)
                    <span class="font-mono text-xs font-bold text-cyan-300">{{ $apt->patient->code }}</span>
                    <span class="text-white/40">-</span>
                  @endif
                  <span class="text-white font-medium truncate">{{ $apt->summary }}</span>
                </div>
                @if($apt->patient)
                  <div class="text-sm text-white/50">{{ $apt->patient->name }}</div>
                @else
                  <div class="text-sm text-amber-400/70">Sin paciente asignado</div>
                @endif
              </div>

              {{-- Status --}}
              <div class="flex-shrink-0">
                @if($apt->nimbus_status === 'confirmed')
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-300">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Confirmada
                  </span>
                @elseif($apt->nimbus_status === 'reminder_sent')
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-300">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"></path></svg>
                    Enviado
                  </span>
                @elseif($apt->nimbus_status === 'cancelled')
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-500/20 text-red-300">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    Cancelada
                  </span>
                @else
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white/10 text-white/60">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path></svg>
                    Pendiente
                  </span>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Quick Actions & Templates --}}
    <div class="space-y-6">
      {{-- Quick Actions --}}
      <div class="bg-white/5 rounded-xl border border-white/10 p-5">
        <h2 class="text-lg font-semibold text-white mb-4">Acciones rápidas</h2>
        <div class="space-y-2">
          <a href="{{ route('calendars.index', ['account' => $account]) }}" class="flex items-center gap-3 p-3 bg-white/5 hover:bg-white/10 rounded-lg transition text-white/80 hover:text-white">
            <div class="w-8 h-8 rounded-lg bg-cyan-500/20 flex items-center justify-center">
              <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <span class="text-sm font-medium">Gestionar calendarios</span>
          </a>
          <a href="{{ route('patients.index') }}" class="flex items-center gap-3 p-3 bg-white/5 hover:bg-white/10 rounded-lg transition text-white/80 hover:text-white">
            <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center">
              <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
            <span class="text-sm font-medium">Ver pacientes</span>
          </a>
          <a href="{{ route('events.index', ['account' => $account]) }}" class="flex items-center gap-3 p-3 bg-white/5 hover:bg-white/10 rounded-lg transition text-white/80 hover:text-white">
            <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center">
              <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
            </div>
            <span class="text-sm font-medium">Sincronizar eventos</span>
          </a>
        </div>
      </div>

      {{-- Templates Status --}}
      <div class="bg-white/5 rounded-xl border border-white/10 p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-white">Mis mensajes</h2>
          <a href="{{ route('templates.index') }}" class="text-cyan-400 hover:text-cyan-300 text-sm font-medium transition">
            Gestionar →
          </a>
        </div>

        <div class="space-y-3">
          {{-- Email Template --}}
          <div class="flex items-center gap-3 p-3 bg-white/5 rounded-lg">
            <div class="w-8 h-8 rounded-lg {{ $emailTemplate ? 'bg-green-500/20' : 'bg-white/10' }} flex items-center justify-center">
              <svg class="w-4 h-4 {{ $emailTemplate ? 'text-green-400' : 'text-white/40' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-white">Email</div>
              <div class="text-xs text-white/50 truncate">
                {{ $emailTemplate ? $emailTemplate->name : 'Usando plantilla por defecto' }}
              </div>
            </div>
            @if($emailTemplate)
              <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            @endif
          </div>

          {{-- SMS Template --}}
          <div class="flex items-center gap-3 p-3 bg-white/5 rounded-lg">
            <div class="w-8 h-8 rounded-lg {{ $smsTemplate ? 'bg-green-500/20' : 'bg-white/10' }} flex items-center justify-center">
              <svg class="w-4 h-4 {{ $smsTemplate ? 'text-green-400' : 'text-white/40' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-white">SMS</div>
              <div class="text-xs text-white/50 truncate">
                {{ $smsTemplate ? $smsTemplate->name : 'Usando plantilla por defecto' }}
              </div>
            </div>
            @if($smsTemplate)
              <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            @endif
          </div>
        </div>

        @if(!$hasTemplates)
          <a href="{{ route('templates.create') }}" class="mt-4 w-full btn btn-primary text-sm justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Crear plantilla personalizada
          </a>
        @endif
      </div>
    </div>
  </div>

  {{-- System Status Footer --}}
  <div class="glass rounded-xl p-4 text-sm">
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-white/50">
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full {{ $isConnected ? 'bg-green-400' : 'bg-white/30' }}"></span>
        <span>{{ $account ?? 'Sin cuenta conectada' }}</span>
      </div>
      <div>{{ $enabledCalendars }} calendario(s) activo(s)</div>
      <div>Última sync: {{ $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() : 'Nunca' }}</div>
    </div>
  </div>
</div>
</x-app-layout>
