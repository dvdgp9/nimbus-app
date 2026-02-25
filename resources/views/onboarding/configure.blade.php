<x-app-layout>
<div class="min-h-screen flex items-center justify-center p-4">
  <div class="max-w-2xl w-full">
    {{-- Progress indicator --}}
    <div class="mb-8">
      <div class="flex items-center justify-center gap-2">
        @for($i = 1; $i <= 5; $i++)
          <div class="w-3 h-3 rounded-full {{ $i <= 4 ? 'bg-cyan-500' : 'bg-white/20' }}"></div>
        @endfor
      </div>
      <p class="text-center text-white/40 text-sm mt-2">Paso 4 de 5</p>
    </div>

    {{-- Main Card --}}
    <div class="bg-white/5 rounded-2xl border border-white/10 p-8">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-lg mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">Configuración de recordatorios</h1>
        <p class="text-white/60">Revisa las plantillas predeterminadas para tus mensajes</p>
      </div>

      {{-- Default Templates Info --}}
      <div class="space-y-6">
        {{-- Email Templates --}}
        <div class="bg-white/5 rounded-xl p-6 border border-white/10">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-white font-semibold flex items-center gap-2">
              <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
              Plantillas de Email
            </h3>
            <span class="text-xs bg-cyan-500/20 text-cyan-300 px-2 py-1 rounded">{{ $emailTemplates->count() }} plantillas</span>
          </div>

          @if($emailTemplates->count() > 0)
            <div class="space-y-2">
              @foreach($emailTemplates->take(3) as $template)
                <div class="flex items-center justify-between py-2 px-3 bg-white/5 rounded-lg">
                  <div>
                    <span class="text-white text-sm">{{ $template->name }}</span>
                    @if($template->is_default)
                      <span class="text-xs bg-emerald-500/20 text-emerald-300 px-1.5 py-0.5 rounded ml-2">Predeterminada</span>
                    @endif
                  </div>
                  @if($template->code)
                    <span class="text-xs font-mono text-cyan-400">{{ $template->code }}</span>
                  @endif
                </div>
              @endforeach
            </div>
          @else
            <p class="text-white/50 text-sm">No hay plantillas de email configuradas. Se usará la plantilla del sistema.</p>
          @endif

          <a href="{{ route('templates.create', ['channel' => 'email']) }}" target="_blank" 
            class="mt-4 inline-flex items-center gap-2 text-cyan-400 hover:text-cyan-300 text-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Crear nueva plantilla de email
          </a>
        </div>

        {{-- SMS Templates --}}
        <div class="bg-white/5 rounded-xl p-6 border border-white/10">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-white font-semibold flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
              </svg>
              Plantillas de SMS
            </h3>
            <span class="text-xs bg-purple-500/20 text-purple-300 px-2 py-1 rounded">{{ $smsTemplates->count() }} plantillas</span>
          </div>

          @if($smsTemplates->count() > 0)
            <div class="space-y-2">
              @foreach($smsTemplates->take(3) as $template)
                <div class="flex items-center justify-between py-2 px-3 bg-white/5 rounded-lg">
                  <div>
                    <span class="text-white text-sm">{{ $template->name }}</span>
                    @if($template->is_default)
                      <span class="text-xs bg-emerald-500/20 text-emerald-300 px-1.5 py-0.5 rounded ml-2">Predeterminada</span>
                    @endif
                  </div>
                  @if($template->code)
                    <span class="text-xs font-mono text-purple-400">{{ $template->code }}</span>
                  @endif
                </div>
              @endforeach
            </div>
          @else
            <p class="text-white/50 text-sm">No hay plantillas de SMS configuradas. Se usará la plantilla del sistema.</p>
          @endif

          <a href="{{ route('templates.create', ['channel' => 'sms']) }}" target="_blank" 
            class="mt-4 inline-flex items-center gap-2 text-purple-400 hover:text-purple-300 text-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Crear nueva plantilla de SMS
          </a>
        </div>

        {{-- Reminder timing info --}}
        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4">
          <div class="flex items-start gap-3">
            <span class="text-amber-400 text-xl">⏰</span>
            <div>
              <p class="text-amber-300 font-semibold">Recordatorios automáticos</p>
              <p class="text-amber-200/70 text-sm mt-1">
                Nimbus enviará recordatorios <strong>48 horas antes</strong> de cada cita. 
                Puedes personalizar esto más adelante en la configuración.
              </p>
            </div>
          </div>
        </div>
      </div>

      {{-- Navigation --}}
      <div class="flex justify-between items-center mt-8 pt-6 border-t border-white/10">
        <form action="{{ route('onboarding.previous') }}" method="POST">
          @csrf
          <button type="submit" class="btn bg-white/5 hover:bg-white/10 text-white/60">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
            </svg>
            Anterior
          </button>
        </form>

        <form action="{{ route('onboarding.next') }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-primary">
            Finalizar configuración
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
</x-app-layout>
