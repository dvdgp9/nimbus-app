<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Próximos eventos • Nimbus</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
  <main class="max-w-5xl mx-auto p-6">
    <header class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold">Próximos 48h</h1>
        <p class="text-sm text-slate-500 mt-1">Cuenta: <strong>{{ $account ?? '—' }}</strong></p>
      </div>
      <div class="flex gap-2">
        <a href="{{ route('google.connect') }}" class="inline-flex items-center rounded-lg bg-slate-700 px-3 py-2 text-white text-sm hover:bg-slate-600">Conectar otra cuenta</a>
        <form method="POST" action="{{ route('events.sync') }}">
          @csrf
          <input type="hidden" name="account" value="{{ $account }}">
          <button class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-white text-sm hover:bg-indigo-500">Sincronizar a BD</button>
        </form>
      </div>
    </header>

    @if (session('status'))
      <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    @error('account')
      <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @if (empty($events))
      <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">No hay eventos que mostrar. Conecta una cuenta y recarga.</div>
    @else
      <div class="grid grid-cols-1 gap-4">
        @foreach ($events as $e)
          <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-baseline justify-between">
              <h3 class="text-base font-semibold">{{ $e['summary'] ?? '(Sin título)' }}</h3>
              <span class="text-xs text-slate-500">{{ $e['calendar_id'] }}</span>
            </div>
            <dl class="mt-2 grid grid-cols-2 gap-2 text-sm">
              <div><dt class="text-slate-500">Inicio</dt><dd class="font-medium">{{ $e['start_at'] }}</dd></div>
              <div><dt class="text-slate-500">Fin</dt><dd class="font-medium">{{ $e['end_at'] }}</dd></div>
              <div><dt class="text-slate-500">Zona</dt><dd class="font-medium">{{ $e['timezone'] ?? '—' }}</dd></div>
              <div><dt class="text-slate-500">Meet</dt><dd class="font-medium truncate">{{ $e['hangout_link'] ?? '—' }}</dd></div>
            </dl>
            @if (!empty($e['description']))
              <details class="mt-3 text-sm text-slate-600">
                <summary class="cursor-pointer select-none text-slate-500">Descripción</summary>
                <pre class="mt-2 whitespace-pre-wrap">{{ $e['description'] }}</pre>
              </details>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </main>
</body>
</html>
