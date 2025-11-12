<x-app-layout>
<div class="page-container">
  {{-- Page Header --}}
  <div class="page-header">
    <h1>Panel de control</h1>
    <p>Gestiona tus calendarios y recordatorios automáticos</p>
  </div>

  {{-- Stats Grid --}}
  <div class="stats-grid">
    <div class="stat-card">
      <div class="label">Cuentas conectadas</div>
      <div class="value">{{ $connectedCount }}</div>
    </div>
    <div class="stat-card">
      <div class="label">Calendarios activos</div>
      <div class="value">{{ $enabledCalendars }}</div>
    </div>
    <div class="stat-card">
      <div class="label">Próximas 48h</div>
      <div class="value">{{ $upcomingAppointments }}</div>
    </div>
    <div class="stat-card">
      <div class="label">Última sync</div>
      <div class="value text-base">{{ $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() : '—' }}</div>
    </div>
  </div>

  {{-- Action Cards --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <a href="{{ route('calendars.index', ['account' => $account]) }}" class="action-card">
      <div class="icon">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
      <h2>Seleccionar calendarios</h2>
      <p>Activa o desactiva los calendarios que deseas sincronizar</p>
    </a>

    <a href="{{ route('events.index', ['account' => $account]) }}" class="action-card">
      <div class="icon">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <h2>Ver eventos</h2>
      <p>Consulta tus próximas citas y sincronízalas a la base de datos</p>
    </a>

    <a href="/email" class="action-card">
      <div class="icon">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
        </svg>
      </div>
      <h2>Probar email</h2>
      <p>Envía un recordatorio de prueba por correo electrónico</p>
    </a>
  </div>

  {{-- Status Info --}}
  <div class="glass rounded-xl p-6">
    <div class="flex items-center gap-3 mb-4">
      <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[var(--nimbus-accent)] to-[var(--nimbus-primary)] flex items-center justify-center">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <h2 class="text-lg font-semibold text-white">Estado del sistema</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div>
        <dt class="text-white/50">Cuenta conectada</dt>
        <dd class="text-white/90 font-medium mt-1">{{ $account ?? 'Ninguna cuenta conectada' }}</dd>
      </div>
      <div>
        <dt class="text-white/50">Última sincronización</dt>
        <dd class="text-white/90 font-medium mt-1">{{ $lastSyncedAt ?? 'Nunca sincronizado' }}</dd>
      </div>
    </div>
  </div>
</div>
</x-app-layout>
