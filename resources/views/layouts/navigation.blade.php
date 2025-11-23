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

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white/70 hover:text-white hover:bg-white/10 focus:outline-none focus:bg-white/10 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (hamburger panel) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                Inicio
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('patients.index')" :active="request()->routeIs('patients.*')">
                Pacientes
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('events.index')" :active="request()->routeIs('events.*')">
                Eventos
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('calendars.index')" :active="request()->routeIs('calendars.*')">
                Calendarios
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        @auth
        <div class="pt-4 pb-1 border-t border-white/10 mb-16">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-white/70">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @endauth
    </div>

    @auth
    <!-- Mobile Bottom Navigation -->
    <div class="sm:hidden" style="position:fixed;bottom:0;left:0;right:0;z-index:40;background:rgba(15,23,42,0.96);border-top:1px solid rgba(255,255,255,0.1);backdrop-filter:blur(12px);">
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
                                    {{-- Iconoir-style home --}}
                                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path d="M4 11.5L12 4l8 7.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M6 10.5V20h12v-9.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                @case('users')
                                    {{-- Iconoir-style users --}}
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <circle cx="9" cy="9" r="3" />
                                        <path d="M4 20a5 5 0 0110 0" stroke-linecap="round" />
                                        <path d="M17 11a2.5 2.5 0 10-2.4-3.2" stroke-linecap="round" />
                                        <path d="M15 20a4 4 0 017 0" stroke-linecap="round" />
                                    </svg>
                                    @break
                                @case('calendar')
                                    {{-- Iconoir-style calendar --}}
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <rect x="4" y="5" width="16" height="15" rx="2" />
                                        <path d="M4 10h16M9 3v4M15 3v4" stroke-linecap="round" />
                                    </svg>
                                    @break
                                @case('layers')
                                    {{-- Iconoir-style layers --}}
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path d="M4 9l8-4 8 4-8 4-8-4z" stroke-linejoin="round" />
                                        <path d="M4 15l8 4 8-4" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                @case('user')
                                    {{-- Iconoir-style user --}}
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
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
</nav>
