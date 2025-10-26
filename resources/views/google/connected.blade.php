<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Google conectado • Nimbus</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
  <main class="max-w-2xl mx-auto p-6">
    <h1 class="text-2xl font-semibold">Google conectado</h1>
    <p class="mt-2 text-slate-600">Has conectado la cuenta <strong>{{ $email }}</strong>.</p>

    <div class="mt-6 flex gap-3">
      <a href="{{ route('events.index') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white text-sm font-medium shadow-sm hover:bg-indigo-500">Ver próximos eventos</a>
    </div>
  </main>
</body>
</html>
