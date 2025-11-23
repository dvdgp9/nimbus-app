<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen nimbus-bg">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="glass shadow-lg">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="py-8 pb-20 sm:pb-8">
                {{ $slot }}
            </main>

            <!-- Mobile Bottom Navigation -->
            @auth
            <div class="fixed bottom-0 inset-x-0 z-40 sm:hidden">
                <div class="max-w-7xl mx-auto px-3 pb-4">
                    <nav class="glass border border-white/10 rounded-2xl bg-slate-900/80 backdrop-blur-md shadow-lg">
                        <div class="flex justify-between">
                            @php
                                $items = [
                                    ['route' => 'home', 'icon' => 'home', 'label' => 'Inicio'],
                                    ['route' => 'patients.index', 'icon' => 'users', 'label' => 'Pacientes'],
                                    ['route' => 'events.index', 'icon' => 'calendar', 'label' => 'Eventos'],
                                    ['route' => 'calendars.index', 'icon' => 'calendar-check', 'label' => 'Calendarios'],
                                    ['route' => 'profile.edit', 'icon' => 'user', 'label' => 'Perfil'],
                                ];
                            @endphp

                            @foreach($items as $item)
                                @php
                                    $isActive = request()->routeIs($item['route']) || str_starts_with($item['route'], 'patients.') && request()->routeIs('patients.*') || str_starts_with($item['route'], 'events.') && request()->routeIs('events.*') || str_starts_with($item['route'], 'calendars.') && request()->routeIs('calendars.*');
                                @endphp
                                <a href="{{ route($item['route']) }}" class="flex-1 flex flex-col items-center justify-center py-2 text-xs {{ $isActive ? 'text-white' : 'text-white/60' }}">
                                    @switch($item['icon'])
                                        @case('home')
                                            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                                <path d="M4 11.5L12 4l8 7.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M6.5 10.5V19a1 1 0 001 1h9a1 1 0 001-1v-8.5" stroke-linecap="round" />
                                            </svg>
                                            @break
                                        @case('users')
                                            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                                <path d="M9 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path d="M21 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path d="M3 15a4 4 0 014-4h0a4 4 0 014 4v2H3v-2z" />
                                                <path d="M13 15a4 4 0 014-4h0a4 4 0 014 4v2h-8v-2z" />
                                            </svg>
                                            @break
                                        @case('calendar')
                                            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                                <rect x="3" y="5" width="18" height="16" rx="2" />
                                                <path d="M3 9h18M9 3v4M15 3v4" stroke-linecap="round" />
                                            </svg>
                                            @break
                                        @case('calendar-check')
                                            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                                <rect x="3" y="5" width="18" height="16" rx="2" />
                                                <path d="M3 9h18M9 3v4M15 3v4" stroke-linecap="round" />
                                                <path d="M10 15l2 2.5 4-5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            @break
                                        @case('user')
                                        @default
                                            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                <path d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                    @endswitch
                                    <span class="mt-0.5 {{ $isActive ? 'font-semibold' : 'font-medium' }}">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </nav>
                </div>
            </div>
            @endauth
        </div>
    </body>
</html>
