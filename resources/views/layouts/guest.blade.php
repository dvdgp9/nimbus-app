<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Nimbus') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            .nimbus-gradient {
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            }
            .glass-panel {
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            .nimbus-accent {
                color: #67e8f9;
            }
        </style>
    </head>
    <body class="font-sans antialiased text-white">
        <div class="min-h-screen flex nimbus-gradient">
            <!-- Left Side: Branding & Info (Hidden on Mobile) -->
            <div class="hidden lg:flex lg:w-1/2 flex-col justify-center px-12 lg:px-24 bg-black/10">
                <div class="max-w-md">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-12 h-12 bg-cyan-400 rounded-xl flex items-center justify-center shadow-lg shadow-cyan-500/20">
                            <svg class="w-8 h-8 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                        </div>
                        <span class="text-3xl font-bold tracking-tight">Nimbus</span>
                    </div>
                    
                    <h1 class="text-5xl font-extrabold leading-tight mb-6">
                        Gestión inteligente para <span class="nimbus-accent">profesionales</span>.
                    </h1>
                    <p class="text-xl text-slate-400 mb-10">
                        Sincroniza tus calendarios, gestiona tus citas y automatiza recordatorios en una sola plataforma diseñada para la eficiencia.
                    </p>
                    
                    <div class="space-y-4 text-slate-300">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 nimbus-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Sincronización con Google Calendar</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 nimbus-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Recordatorios automáticos</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 nimbus-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Gestión de pacientes centralizada</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Auth Form -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 relative overflow-hidden">
                <!-- Decorative Circles -->
                <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-cyan-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl"></div>

                <div class="w-full max-w-[440px] z-10">
                    <div class="lg:hidden flex items-center justify-center gap-2 mb-8">
                        <div class="w-8 h-8 bg-cyan-400 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                        </div>
                        <span class="text-2xl font-bold">Nimbus</span>
                    </div>

                    <div class="glass-panel p-8 sm:p-10 rounded-3xl shadow-2xl">
                        {{ $slot }}
                    </div>

                    <p class="text-center mt-8 text-slate-500 text-sm">
                        &copy; {{ date('Y') }} Nimbus Platform.
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
