<x-app-layout>
<div class="page-container max-w-4xl">
    {{-- Page Header --}}
    <div class="page-header mb-8">
        <h1>Configuración</h1>
        <p>Gestiona tu cuenta, calendarios y preferencias</p>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-6">
        <a href="{{ route('profile.edit') }}" class="px-4 py-2 rounded-lg font-medium transition bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
            <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Mi cuenta
        </a>
        <a href="{{ route('calendars.index') }}" class="px-4 py-2 rounded-lg font-medium transition bg-white/5 text-white/60 hover:bg-white/10 hover:text-white/80">
            <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Calendarios
        </a>
    </div>

    <div class="space-y-6">
        {{-- Profile Information --}}
        <div class="bg-white/5 rounded-xl border border-white/10 p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        {{-- Password --}}
        <div class="bg-white/5 rounded-xl border border-white/10 p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        {{-- Delete Account --}}
        <div class="bg-white/5 rounded-xl border border-red-500/20 p-6">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
</x-app-layout>
