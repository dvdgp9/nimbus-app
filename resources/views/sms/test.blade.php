<x-app-layout>

<div class="page-container max-w-2xl">
  {{-- Page Header --}}
  <div class="mb-8">
    <div class="page-header">
      <h1>Prueba de SMS</h1>
      <p>Envía un SMS de prueba para verificar la configuración de Acumbamail</p>
    </div>
  </div>

  {{-- Credits Info --}}
  @if($credits !== null)
    <div class="bg-cyan-500/10 border border-cyan-500/30 rounded-xl p-4 mb-6 flex items-center gap-3">
      <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <span class="text-cyan-300">Créditos SMS disponibles: <strong>{{ number_format($credits, 2) }}</strong></span>
    </div>
  @endif

  {{-- Alerts --}}
  @if (session('success'))
    <div class="alert alert-success mb-6">
      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ session('success') }}
    </div>
  @endif

  @error('sms')
    <div class="alert alert-error mb-6">
      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ $message }}
    </div>
  @enderror

  {{-- Form --}}
  <form method="POST" action="{{ route('sms.send') }}" class="space-y-6">
    @csrf

    <div class="bg-white/5 rounded-xl border border-white/10 p-6 space-y-6">
      {{-- Phone --}}
      <div>
        <label for="phone" class="block text-sm font-medium text-white/80 mb-2">
          Número de teléfono <span class="text-red-400">*</span>
        </label>
        <input 
          type="text" 
          id="phone" 
          name="phone" 
          value="{{ old('phone') }}"
          required
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('phone') border-red-500/50 @enderror"
          placeholder="+34612345678"
        >
        @error('phone')
          <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-white/40">Formato internacional: +34XXXXXXXXX</p>
      </div>

      {{-- Message --}}
      <div>
        <label for="message" class="block text-sm font-medium text-white/80 mb-2">
          Mensaje <span class="text-red-400">*</span>
        </label>
        <textarea 
          id="message" 
          name="message" 
          rows="3"
          maxlength="160"
          required
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('message') border-red-500/50 @enderror"
          placeholder="Escribe tu mensaje de prueba..."
        >{{ old('message', 'Hola! Este es un SMS de prueba desde Nimbus.') }}</textarea>
        @error('message')
          <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-white/40">
          <span id="char-count">{{ strlen(old('message', 'Hola! Este es un SMS de prueba desde Nimbus.')) }}</span>/160 caracteres
        </p>
      </div>

      {{-- Sender Info --}}
      <div class="bg-white/5 rounded-lg p-4 border border-white/10">
        <div class="flex items-center gap-2 text-white/60 text-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span>El SMS se enviará con el remitente: <strong class="text-white">{{ config('services.acumbamail.sender', 'Nimbus') }}</strong></span>
        </div>
      </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3 justify-end">
      <a href="{{ route('home') }}" class="btn bg-white/5 hover:bg-white/10 text-white">
        Volver
      </a>
      <button type="submit" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
        </svg>
        Enviar SMS de prueba
      </button>
    </div>
  </form>
</div>

<script>
  document.getElementById('message').addEventListener('input', function() {
    document.getElementById('char-count').textContent = this.value.length;
  });
</script>

</x-app-layout>
