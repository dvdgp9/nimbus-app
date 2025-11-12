<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Nimbus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0b1020 0%, #1a2332 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white/10 backdrop-blur-lg rounded-2xl p-8 shadow-2xl border border-white/20">
        <div class="text-center">
            <div class="text-6xl mb-4">
                @if(str_contains($title, 'confirmada'))
                    ✅
                @elseif(str_contains($title, 'cancelada'))
                    ❌
                @else
                    ℹ️
                @endif
            </div>
            
            <h1 class="text-3xl font-bold text-white mb-4">{{ $title }}</h1>
            <p class="text-gray-300 text-lg mb-6">{{ $message }}</p>
            
            @if(isset($appointment))
            <div class="bg-white/5 rounded-lg p-4 mb-6 text-left">
                <h2 class="text-white font-semibold mb-2">Detalles de la cita:</h2>
                <p class="text-gray-300 text-sm">
                    <strong>Título:</strong> {{ $appointment->summary }}<br>
                    <strong>Fecha:</strong> {{ $appointment->formatted_date }}<br>
                    <strong>Hora:</strong> {{ $appointment->formatted_time }}
                </p>
            </div>
            @endif
            
            <a href="{{ url('/') }}" class="inline-block bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:opacity-90 transition">
                Volver al inicio
            </a>
        </div>
    </div>
</body>
</html>
