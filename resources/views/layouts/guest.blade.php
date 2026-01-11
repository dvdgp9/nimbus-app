<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Nimbus') }} - Gestión de Citas</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen nimbus-bg flex">
            <!-- Left side - Welcome Section (hidden on mobile) -->
            <div class="hidden lg:flex lg:w-1/2 flex-col justify-center px-12 xl:px-20 text-white">
                <div class="max-w-xl">
                    <h1 class="text-5xl font-bold mb-6" style="text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                        Bienvenido a Nimbus
                    </h1>
                    <p class="text-xl mb-8 leading-relaxed" style="text-shadow: 0 1px 5px rgba(0,0,0,0.2);">
                        Tu plataforma inteligente para la gestión de citas médicas y recordatorios automáticos.
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h3 class="font-semibold text-lg">Sincronización con Google Calendar</h3>
                                <p class="text-cyan-100">Conecta tus calendarios y gestiona todo desde un solo lugar</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h3 class="font-semibold text-lg">Recordatorios automáticos</h3>
                                <p class="text-cyan-100">Envía notificaciones a tus pacientes vía WhatsApp</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h3 class="font-semibold text-lg">Gestión de pacientes</h3>
                                <p class="text-cyan-100">Organiza la información de tus pacientes de forma segura</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Login Form -->
            <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
                <div class="w-full max-w-md">
                    <!-- Logo and Title (visible on mobile) -->
                    <div class="text-center mb-8 lg:hidden">
                        <h1 class="text-3xl font-bold text-white mb-2" style="text-shadow: 0 2px 8px rgba(0,0,0,0.3);">Nimbus</h1>
                        <p class="text-cyan-100" style="text-shadow: 0 1px 4px rgba(0,0,0,0.2);">Gestión inteligente de citas médicas</p>
                    </div>

                    <!-- Login Card -->
                    <div class="glass p-8 rounded-2xl shadow-2xl">
                        {{ $slot }}
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-white" style="text-shadow: 0 1px 3px rgba(0,0,0,0.3);">
                            ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-semibold hover:underline text-cyan-300">Regístrate aquí</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
