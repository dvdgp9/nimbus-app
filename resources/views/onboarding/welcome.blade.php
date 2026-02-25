<x-app-layout>
<div class="min-h-screen flex items-center justify-center p-4">
  <div class="max-w-2xl w-full">
    {{-- Progress indicator --}}
    <div class="mb-8">
      <div class="flex items-center justify-center gap-2">
        @for($i = 1; $i <= 5; $i++)
          <div class="w-3 h-3 rounded-full {{ $i === 1 ? 'bg-cyan-500' : 'bg-white/20' }}"></div>
        @endfor
      </div>
      <p class="text-center text-white/40 text-sm mt-2">Paso 1 de 5</p>
    </div>

    {{-- Welcome Card --}}
    <div class="bg-white/5 rounded-2xl border border-white/10 p-8 md:p-12 text-center">
      {{-- Logo --}}
      <div class="mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl shadow-lg shadow-cyan-500/20">
          <span class="text-4xl">☁️</span>
        </div>
      </div>

      {{-- Title --}}
      <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
        ¡Bienvenida a Nimbus!
      </h1>

      {{-- Description --}}
      <p class="text-white/70 text-lg mb-8 max-w-md mx-auto">
        Vamos a configurar tu cuenta en unos sencillos pasos para que puedas empezar a gestionar tus citas automáticamente.
      </p>

      {{-- What we'll do --}}
      <div class="bg-white/5 rounded-xl p-6 mb-8 text-left">
        <h3 class="text-white font-semibold mb-4">En los próximos minutos:</h3>
        <ul class="space-y-3">
          <li class="flex items-start gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-cyan-500/20 text-cyan-400 rounded-full flex items-center justify-center text-sm font-bold">1</span>
            <span class="text-white/80"><strong class="text-white">Importarás tus pacientes</strong> — desde un archivo CSV o uno a uno</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-cyan-500/20 text-cyan-400 rounded-full flex items-center justify-center text-sm font-bold">2</span>
            <span class="text-white/80"><strong class="text-white">Conectarás Google Calendar</strong> — para sincronizar tus citas</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-cyan-500/20 text-cyan-400 rounded-full flex items-center justify-center text-sm font-bold">3</span>
            <span class="text-white/80"><strong class="text-white">Configurarás los recordatorios</strong> — plantillas y preferencias</span>
          </li>
        </ul>
      </div>

      {{-- Important note --}}
      <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 mb-8 text-left">
        <div class="flex items-start gap-3">
          <span class="text-amber-400 text-xl">💡</span>
          <p class="text-amber-200/80 text-sm">
            <strong class="text-amber-300">Importante:</strong> Es mejor importar tus pacientes <strong>antes</strong> de conectar el calendario, así Nimbus podrá reconocerlos automáticamente en tus citas.
          </p>
        </div>
      </div>

      {{-- Action button --}}
      <form action="{{ route('onboarding.next') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg w-full md:w-auto px-12">
          Comenzar configuración
          <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
          </svg>
        </button>
      </form>
    </div>

    {{-- Skip option --}}
    <div class="mt-6 text-center">
      <form action="{{ route('onboarding.skip') }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="text-white/40 hover:text-white/60 text-sm transition">
          Ya tengo todo configurado, saltar
        </button>
      </form>
    </div>
  </div>
</div>
</x-app-layout>
