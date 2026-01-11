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
            <main class="py-8">
                @yield('content')
                {{ $slot ?? '' }}
            </main>
        </div>

        @auth
        <!-- Mobile Bottom Navigation -->
        <div class="sm:hidden" style="position:fixed;bottom:0;left:0;right:0;z-index:50;background:rgba(15,23,42,0.96);border-top:1px solid rgba(255,255,255,0.1);backdrop-filter:blur(12px);">
            <div class="max-w-7xl mx-auto" style="padding:4px 8px;">
                <div style="display:flex;justify-content:space-between;">
                    @php
                        $navItems = [
                            [
                                'label' => 'Inicio',
                                'route' => 'home',
                                'active' => request()->routeIs('home'),
                                'icon' => 'home',
                            ],
                            [
                                'label' => 'Pacientes',
                                'route' => 'patients.index',
                                'active' => request()->routeIs('patients.*'),
                                'icon' => 'users',
                            ],
                            [
                                'label' => 'Eventos',
                                'route' => 'events.index',
                                'active' => request()->routeIs('events.*'),
                                'icon' => 'calendar',
                            ],
                            [
                                'label' => 'Calendarios',
                                'route' => 'calendars.index',
                                'active' => request()->routeIs('calendars.*'),
                                'icon' => 'layers',
                            ],
                            [
                                'label' => 'Perfil',
                                'route' => 'profile.edit',
                                'active' => request()->routeIs('profile.*'),
                                'icon' => 'user',
                            ],
                        ];
                    @endphp

                    @foreach($navItems as $item)
                        @php $isActive = $item['active']; @endphp
                        <a href="{{ route($item['route']) }}" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:6px 0;font-size:11px;text-decoration:none;{{ $isActive ? 'color:#67e8f9;' : 'color:rgba(255,255,255,0.7);' }}">
                            <span style="margin-bottom:2px;display:inline-flex;align-items:center;justify-content:center;">
                                @switch($item['icon'])
                                    @case('home')
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                            <path d="M4 11.5L12 4l8 7.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M6 10.5V20h12v-9.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('users')
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                            <circle cx="9" cy="9" r="3" />
                                            <path d="M4 20a5 5 0 0110 0" stroke-linecap="round" />
                                            <path d="M17 11a2.5 2.5 0 10-2.4-3.2" stroke-linecap="round" />
                                            <path d="M15 20a4 4 0 017 0" stroke-linecap="round" />
                                        </svg>
                                        @break
                                    @case('calendar')
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                            <rect x="4" y="5" width="16" height="15" rx="2" />
                                            <path d="M4 10h16M9 3v4M15 3v4" stroke-linecap="round" />
                                        </svg>
                                        @break
                                    @case('layers')
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                            <path d="M4 9l8-4 8 4-8 4-8-4z" stroke-linejoin="round" />
                                            <path d="M4 15l8 4 8-4" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('user')
                                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                            <circle cx="12" cy="9" r="3.2" />
                                            <path d="M6 20a6 6 0 0112 0" stroke-linecap="round" />
                                        </svg>
                                        @break
                                @endswitch
                            </span>
                            <span style="letter-spacing:0.04em;">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endauth
    </body>
</html>
