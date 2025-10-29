<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nimbus • Panel</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
  <header class="border-b border-slate-200 bg-white">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <h1 class="text-xl font-semibold">Nimbus</h1>
      <nav class="flex items-center gap-4 text-sm">
        <a href="/" class="text-slate-600 hover:text-slate-900">Inicio</a>
        <a href="/calendars" class="text-slate-600 hover:text-slate-900">Calendarios</a>
        <a href="/events" class="text-slate-600 hover:text-slate-900">Eventos</a>
        <a href="/email" class="text-slate-600 hover:text-slate-900">Prueba email</a>
      </nav>
    </div>
  </header>

  <main class="max-w-6xl mx-auto p-6">
    <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="text-xs text-slate-500">Cuentas conectadas</div>
        <div class="mt-1 text-2xl font-semibold">{{ $connectedCount }}</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="text-xs text-slate-500">Calendarios habilitados</div>
        <div class="mt-1 text-2xl font-semibold">{{ $enabledCalendars }}</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="text-xs text-slate-500">Próximas 48h</div>
        <div class="mt-1 text-2xl font-semibold">{{ $upcomingAppointments }}</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="text-xs text-slate-500">Última sync</div>
        <div class="mt-1 text-2xl font-semibold">{{ $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() : '—' }}</div>
      </div>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <a href="{{ route('google.connect') }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm transition">
        <h2 class="font-semibold">Conectar Google</h2>
        <p class="mt-1 text-sm text-slate-500">Autoriza acceso de solo lectura a tus calendarios.</p>
      </a>
      <a href="{{ route('calendars.index', ['account' => $account]) }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm transition">
        <h2 class="font-semibold">Seleccionar calendarios</h2>
        <p class="mt-1 text-sm text-slate-500">Activa/desactiva los calendarios a sincronizar.</p>
      </a>
      <a href="{{ route('events.index', ['account' => $account]) }}" class="rounded-xl border border-slate-200 bg-white p-5 hover:shadow-sm transition">
        <h2 class="font-semibold">Ver eventos</h2>
        <p class="mt-1 text-sm text-slate-500">Consulta las próximas 48h y sincroniza a la BD.</p>
      </a>
    </section>

    <section class="mt-8">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="font-semibold">Estado</h2>
        <ul class="mt-2 text-sm text-slate-600 list-disc pl-5">
          <li><strong>Cuenta actual:</strong> {{ $account ?? '—' }}</li>
          <li><strong>Última sync:</strong> {{ $lastSyncedAt ?? '—' }}</li>
        </ul>
      </div>
    </section>
  </main>
</body>
</html>
