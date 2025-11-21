<x-app-layout>


<div class="page-container max-w-3xl">
  {{-- Page Header --}}
  <div class="page-header">
    <h1>Probar recordatorio por email</h1>
    <p>Envía un correo de prueba con el mensaje personalizado</p>
  </div>

  {{-- Alerts --}}
  @if (session('status'))
    <div class="alert alert-success mb-6">
      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ session('status') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-error mb-6">
      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <ul class="list-disc pl-5 space-y-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Form --}}
  <div class="glass rounded-xl p-6">
    <form method="POST" action="{{ route('email.send') }}" class="space-y-6">
      @csrf

      <div class="form-group">
        <label for="to" class="form-label">
          <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          Destinatario
        </label>
        <input 
          id="to" 
          name="to" 
          type="email" 
          required
          value="{{ old('to') }}"
          class="form-input" 
          placeholder="paciente@ejemplo.com">
      </div>

      <div class="form-group">
        <label for="subject" class="form-label">
          <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
          </svg>
          Asunto
        </label>
        <input 
          id="subject" 
          name="subject" 
          type="text" 
          required
          value="{{ old('subject', 'Recordatorio de tu sesión') }}"
          class="form-input" 
          placeholder="Recordatorio de tu sesión">
      </div>

      <div class="form-group">
        <label for="message" class="form-label">
          <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
          </svg>
          Mensaje del recordatorio
        </label>
        <textarea 
          id="message" 
          name="message" 
          rows="12" 
          required
          class="form-input font-mono text-sm"
          placeholder="Escribe tu mensaje aquí...">{{ old('message', $defaultMessage) }}</textarea>
        <p class="mt-2 text-xs text-white/50 flex items-start gap-2">
          <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span>Usa placeholders como <code class="bg-white/10 px-1.5 py-0.5 rounded">&#123;&#123;nombre&#125;&#125;</code>, <code class="bg-white/10 px-1.5 py-0.5 rounded">&#123;&#123;fecha&#125;&#125;</code>, <code class="bg-white/10 px-1.5 py-0.5 rounded">&#123;&#123;hora&#125;&#125;</code>, <code class="bg-white/10 px-1.5 py-0.5 rounded">&#123;&#123;tz&#125;&#125;</code> y <code class="bg-white/10 px-1.5 py-0.5 rounded">&#123;&#123;enlace&#125;&#125;</code></span>
        </p>
      </div>

      <div class="flex items-center justify-end gap-3 pt-4 border-t border-white/10">
        <a href="/" class="btn btn-ghost">
          Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          Enviar recordatorio
        </button>
      </div>
    </form>
  </div>

  {{-- Info Card --}}
  <div class="glass rounded-xl p-6 mt-6">
    <div class="flex items-start gap-3">
      <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[var(--nimbus-warm)] to-[var(--nimbus-primary)] flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
      </div>
      <div class="text-sm text-white/70">
        <p class="font-medium text-white/90 mb-1">Modo de prueba</p>
        <p>Esta funcionalidad envía emails usando la configuración SMTP de tu archivo <code class="bg-white/10 px-1.5 py-0.5 rounded">.env</code>. En producción, los recordatorios se enviarán automáticamente según la programación de eventos.</p>
      </div>
    </div>
  </div>
</div>
</x-app-layout>
