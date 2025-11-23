<nav x-data="{ open: false }" class="glass border-b border-white/10">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="text-white font-bold text-xl">
                        Nimbus
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                        Inicio
                    </x-nav-link>
                    <x-nav-link :href="route('patients.index')" :active="request()->routeIs('patients.*')">
                        Pacientes
                    </x-nav-link>
                    <x-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')">
                        Eventos
                    </x-nav-link>
                    <x-nav-link :href="route('calendars.index')" :active="request()->routeIs('calendars.*')">
                        Calendarios
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            @auth
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white/90 hover:text-white focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            @endauth

            <!-- Hamburger - Hidden, using bottom nav instead -->
            <div class="-me-2 flex items-center sm:hidden lg:flex lg:hidden">
                <!-- Intentionally empty - bottom nav replaces hamburger menu -->
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation (hidden on desktop) -->
    @auth
    <div x-data="{ profileOpen: false }" class="lg:hidden">
        <!-- Bottom Nav Bar -->
        <div class="fixed bottom-0 left-0 right-0 glass border-t border-white/10 safe-area-inset z-50">
            <nav class="max-w-md mx-auto px-4">
                <ul class="flex items-center justify-around h-16">
                    <!-- Home -->
                    <li class="flex-1">
                        <a href="{{ route('home') }}" class="flex flex-col items-center justify-center gap-1 py-2 transition-colors {{ request()->routeIs('home') ? 'text-cyan-400' : 'text-white/60 hover:text-white/90' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M3 9.5L12 4l9 5.5M19 13v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="text-xs font-medium">Inicio</span>
                        </a>
                    </li>
                    
                    <!-- Pacientes -->
                    <li class="flex-1">
                        <a href="{{ route('patients.index') }}" class="flex flex-col items-center justify-center gap-1 py-2 transition-colors {{ request()->routeIs('patients.*') ? 'text-cyan-400' : 'text-white/60 hover:text-white/90' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M1 20v-1a7 7 0 017-7v0a7 7 0 017 7v1M8 12a4 4 0 100-8 4 4 0 000 8zM16 12a4 4 0 100-8 4 4 0 000 8zM16 20v-1a7 7 0 00-3-5.743" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="text-xs font-medium">Pacientes</span>
                        </a>
                    </li>
                    
                    <!-- Eventos -->
                    <li class="flex-1">
                        <a href="{{ route('events.index') }}" class="flex flex-col items-center justify-center gap-1 py-2 transition-colors {{ request()->routeIs('events.*') ? 'text-cyan-400' : 'text-white/60 hover:text-white/90' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <rect x="3" y="6" width="18" height="15" rx="2"/>
                                <path d="M3 10h18M8 3v6M16 3v6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="text-xs font-medium">Eventos</span>
                        </a>
                    </li>
                    
                    <!-- Calendarios -->
                    <li class="flex-1">
                        <a href="{{ route('calendars.index') }}" class="flex flex-col items-center justify-center gap-1 py-2 transition-colors {{ request()->routeIs('calendars.*') ? 'text-cyan-400' : 'text-white/60 hover:text-white/90' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M6 6h12M6 6V4M6 6v12a2 2 0 002 2h8a2 2 0 002-2V6M18 6V4M10 10h4M10 14h4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="text-xs font-medium">Calendarios</span>
                        </a>
                    </li>
                    
                    <!-- Perfil -->
                    <li class="flex-1">
                        <button @click="profileOpen = true" class="flex flex-col items-center justify-center gap-1 py-2 w-full transition-colors {{ request()->routeIs('profile.*') ? 'text-cyan-400' : 'text-white/60 hover:text-white/90' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M12 12a4 4 0 100-8 4 4 0 000 8zM5 20v-1a7 7 0 0114 0v1" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="text-xs font-medium">Perfil</span>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Profile Modal/Sheet -->
        <div x-show="profileOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="profileOpen = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-end"
             style="display: none;">
            <div @click.stop 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full"
                 class="w-full glass rounded-t-2xl border-t border-white/10 safe-area-inset">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                    <div>
                        <div class="font-semibold text-white text-lg">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-white/60">{{ Auth::user()->email }}</div>
                    </div>
                    <button @click="profileOpen = false" class="p-2 rounded-lg hover:bg-white/10 transition-colors text-white/60 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Menu Items -->
                <div class="p-4 space-y-2">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/5 transition-colors text-white/80 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path d="M12 12a4 4 0 100-8 4 4 0 000 8zM5 20v-1a7 7 0 0114 0v1" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="font-medium">Perfil</span>
                    </a>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-500/10 transition-colors text-red-400 hover:text-red-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M15 12H2m0 0l3.5-3M2 12l3.5 3M15 3h2a2 2 0 012 2v14a2 2 0 01-2 2h-2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="font-medium">Cerrar Sesión</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endauth
</nav>
