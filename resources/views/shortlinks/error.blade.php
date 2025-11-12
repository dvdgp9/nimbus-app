<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Nimbus</title>
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
            <div class="text-6xl mb-4">⚠️</div>
            
            <h1 class="text-3xl font-bold text-white mb-4">{{ $message }}</h1>
            <p class="text-gray-300 text-lg mb-6">{{ $detail }}</p>
            
            <div class="space-y-3">
                <a href="{{ url('/') }}" class="block bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:opacity-90 transition">
                    Volver al inicio
                </a>
                
                <p class="text-gray-400 text-sm">
                    ¿Necesitas ayuda? Contacta con nosotros por 
                    <a href="https://wa.me/{{ config('services.whatsapp.professional_phone') }}" class="text-cyan-400 hover:underline">WhatsApp</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
