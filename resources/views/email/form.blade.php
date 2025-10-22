<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar recordatorio • Nimbus</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <div class="max-w-2xl mx-auto p-6">
        <header class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight">Enviar recordatorio</h1>
            <p class="text-sm text-slate-500 mt-1">MVP de envío por SMTP configurado en tu <code>.env</code>.</p>
        </header>

        @if (session('status'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('email.send') }}" class="space-y-6 bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            @csrf

            <div>
                <label for="to" class="block text-sm font-medium text-slate-700">Destinatario</label>
                <input id="to" name="to" type="email" required
                       value="{{ old('to') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="nombre@dominio.com">
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-slate-700">Asunto</label>
                <input id="subject" name="subject" type="text" required
                       value="{{ old('subject', 'Recordatorio de tu sesión') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Asunto del correo">
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-slate-700">Mensaje</label>
                <textarea id="message" name="message" rows="10" required
                          class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contenido del recordatorio">{{ old('message', "Hola @{{nombre}},\n\nTe recordamos tu sesión programada para el @{{fecha}} a las @{{hora}} (zona horaria: @{{tz}}).\n\nEnlace de la videollamada: @{{enlace}}\n\nSi necesitas reprogramar o cancelar, por favor responde a este correo.\n\nGracias,\nEquipo Nimbus") }}</textarea>
                <p class="mt-2 text-xs text-slate-500">Puedes usar placeholders como {{'{{nombre}}'}}, {{'{{fecha}}'}}, {{'{{hora}}'}}, {{'{{tz}}'}}, {{'{{enlace}}'}} y personalizarlos manualmente en esta prueba.</p>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white text-sm font-medium shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Enviar recordatorio
                </button>
            </div>
        </form>

        <footer class="mt-10 text-center text-xs text-slate-400">
            Nimbus • MVP de recordatorios por email
        </footer>
    </div>
</body>
</html>
