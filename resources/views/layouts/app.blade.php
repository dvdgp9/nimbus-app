<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Nimbus') • Recordatorios inteligentes</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen">
  <div class="min-h-screen flex flex-col">
    {{-- Navigation --}}
    <header class="glass border-b border-white/20 sticky top-0 z-50 backdrop-blur-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          {{-- Logo --}}
          <a href="/" class="flex items-center gap-3">
            <div class="relative">
              <div class="absolute inset-0 bg-gradient-to-br from-[var(--nimbus-accent)] to-[var(--nimbus-primary)] rounded-lg blur opacity-60 transition"></div>
              <div class="relative bg-gradient-to-br from-[var(--nimbus-accent)] to-[var(--nimbus-primary)] rounded-lg p-2">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                </svg>
              </div>
            </div>
            <span class="text-xl font-bold bg-gradient-to-r from-[var(--nimbus-accent)] to-[var(--nimbus-primary)] bg-clip-text text-transparent">
              Nimbus
            </span>
          </a>

          {{-- Navigation Links --}}
          <nav class="hidden md:flex items-center gap-1">
            <a href="/" class="nav-link {{ request()->is('/') ? 'active' : '' }}">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              <span>Inicio</span>
            </a>
            <a href="/calendars" class="nav-link {{ request()->is('calendars*') ? 'active' : '' }}">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span>Calendarios</span>
            </a>
            <a href="/events" class="nav-link {{ request()->is('events*') ? 'active' : '' }}">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>Eventos</span>
            </a>
            <a href="/email" class="nav-link {{ request()->is('email*') ? 'active' : '' }}">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
              <span>Email</span>
            </a>
          </nav>

          {{-- Actions --}}
          <div class="flex items-center gap-3">
            <a href="{{ route('google.connect') }}" class="btn btn-primary hidden sm:inline-flex">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              <span>Conectar Google</span>
            </a>
            
            {{-- Mobile menu button --}}
            <button id="mobile-menu-button" class="md:hidden p-2 rounded-lg hover:bg-white/10 transition">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            </button>
          </div>
        </div>

        {{-- Mobile Navigation --}}
        <div id="mobile-menu" class="hidden md:hidden border-t border-white/10 py-4">
          <nav class="flex flex-col gap-2">
            <a href="/" class="nav-link {{ request()->is('/') ? 'active' : '' }}">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              <span>Inicio</span>
            </a>
            <a href="/calendars" class="nav-link {{ request()->is('calendars*') ? 'active' : '' }}">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span>Calendarios</span>
            </a>
            <a href="/events" class="nav-link {{ request()->is('events*') ? 'active' : '' }}">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>Eventos</span>
            </a>
            <a href="/email" class="nav-link {{ request()->is('email*') ? 'active' : '' }}">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
              <span>Email</span>
            </a>
            <a href="{{ route('google.connect') }}" class="btn btn-primary justify-center mt-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              <span>Conectar Google</span>
            </a>
          </nav>
        </div>
      </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1">
      @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="glass border-t border-white/10 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="text-sm text-white/60">
            <span class="font-medium text-white/80">Nimbus</span> • Recordatorios inteligentes para consultas online
          </div>
          <div class="flex items-center gap-4 text-xs text-white/50">
            <a href="#" class="hover:text-white/80 transition">Documentación</a>
            <a href="#" class="hover:text-white/80 transition">Soporte</a>
            <span>v1.0.0</span>
          </div>
        </div>
      </div>
    </footer>
  </div>
</body>
</html>
