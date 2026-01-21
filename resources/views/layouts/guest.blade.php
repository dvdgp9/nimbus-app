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
    <body class="font-sans antialiased nimbus-bg text-gray-900">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Left side - Welcome Section (hidden on mobile) -->
            <div class="hidden lg:flex lg:w-1/2 flex-col justify-center px-12 xl:px-20 text-white bg-black/20 backdrop-blur-sm border-r border-white/10">
                <div class="max-w-xl">
                    <div class="mb-12">
                        <x-application-logo class="w-24 h-24 fill-current text-cyan-400" />
                    </div>
                    <h1 class="text-5xl font-bold mb-6 leading-tight" style="text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                        Bienvenido a <span class="text-cyan-400">Nimbus</span>
                    </h1>
                    <p class="text-xl mb-12 text-white/90 leading-relaxed" style="text-shadow: 0 1px 5px rgba(0,0,0,0.2);">
                        Tu plataforma inteligente para la gestión de citas médicas y recordatorios automáticos.
                    </p>
                    <div class="space-y-8">
                        <div class="flex items-start gap-4">
                            <div class="p-2 bg-white/10 rounded-lg backdrop-blur-md">
                                <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg text-white">Sincronización con Google Calendar</h3>
                                <p class="text-white/60">Conecta tus calendarios y gestiona todo desde un solo lugar</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="p-2 bg-white/10 rounded-lg backdrop-blur-md">
                                <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 012 2V6a2 2 0 01-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg text-white">Recordatorios automáticos</h3>
                                <p class="text-white/60">Envía notificaciones a tus pacientes vía Email y SMS</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="p-2 bg-white/10 rounded-lg backdrop-blur-md">
                                <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg text-white">Gestión de pacientes</h3>
                                <p class="text-white/60">Organiza la información de tus pacientes de forma segura</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Login Form -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
                <div class="w-full max-w-md">
                    <!-- Logo and Title (visible on mobile) -->
                    <div class="text-center mb-10 lg:hidden">
                        <div class="flex justify-center mb-4">
                            <x-application-logo class="w-16 h-16 fill-current text-cyan-400" />
                        </div>
                        <h1 class="text-4xl font-bold text-white mb-2" style="text-shadow: 0 2px 8px rgba(0,0,0,0.3);">Nimbus</h1>
                        <p class="text-cyan-100/80">Gestión inteligente de citas médicas</p>
                    </div>

                    <!-- Login Card -->
                    <div class="glass p-8 sm:p-10 rounded-3xl shadow-2xl relative overflow-hidden">
                        <!-- Decorative element -->
                        <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-500/10 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-blue-500/10 rounded-full blur-3xl"></div>
                        
                        <div class="relative z-10">
                            {{ $slot }}
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-8 text-center relative z-10">
                        <p class="text-sm text-white/70" style="text-shadow: 0 1px 3px rgba(0,0,0,0.3);">
                            ¿No tienes cuenta? <a href="{{ route('register') }}" class="font-bold hover:underline text-cyan-400 transition-colors">Regístrate gratis</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
