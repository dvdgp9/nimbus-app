<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calendarios • Nimbus</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
  <main class="max-w-3xl mx-auto p-6">
    <header class="mb-6">
      <h1 class="text-2xl font-semibold">Seleccionar calendarios</h1>
      <p class="text-sm text-slate-500">Cuenta: <strong>{{ $account }}</strong></p>
    </header>

    <form method="POST" action="{{ route('calendars.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
      @csrf
      <input type="hidden" name="account" value="{{ $account }}">

      <div class="space-y-3">
        @forelse ($calendars as $cal)
          <label class="flex items-start gap-3">
            <input type="checkbox" name="calendars[]" value="{{ $cal['id'] }}" class="mt-1"
              {{ (isset($enabled[$cal['id']]) && $enabled[$cal['id']]) || $cal['primary'] ? 'checked' : '' }}>
            <div>
              <div class="font-medium">{{ $cal['summary'] ?? $cal['id'] }}</div>
              <div class="text-xs text-slate-500">{{ $cal['id'] }} @if($cal['primary']) • principal @endif</div>
            </div>
          </label>
        @empty
          <p class="text-sm text-slate-500">No se encontraron calendarios.</p>
        @endforelse
      </div>

      <div class="mt-6 flex gap-2">
        <button class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white text-sm hover:bg-indigo-500">Guardar</button>
        <a class="inline-flex items-center rounded-lg bg-slate-700 px-4 py-2 text-white text-sm hover:bg-slate-600" href="{{ route('events.index', ['account' => $account]) }}">Ver eventos</a>
      </div>
    </form>
  </main>
</body>
</html>
