<x-app-layout>

@php
  $isEmail = $channel === 'email';
  $isSms = $channel === 'sms';
@endphp

<div class="page-container max-w-5xl">
  {{-- Page Header --}}
  <div class="mb-8">
    <a href="{{ request('from_onboarding') ? route('onboarding.step', ['step' => 4]) : route('templates.index', ['channel' => $channel]) }}" class="text-cyan-400 hover:text-cyan-300 transition inline-flex items-center gap-1 mb-4">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
      </svg>
      {{ request('from_onboarding') ? 'Volver al onboarding' : 'Volver a plantillas' }}
    </a>
    <div class="page-header">
      <h1>Nueva Plantilla de {{ $channel === 'email' ? 'Email' : 'SMS' }}</h1>
      <p>Crea un mensaje personalizado para tus recordatorios</p>
    </div>
  </div>

  {{-- Form --}}
  <form method="POST" action="{{ route('templates.store') }}" class="space-y-6" id="template-form">
    @csrf
    <input type="hidden" name="channel" value="{{ $channel }}">
    @if(request('from_onboarding'))
      <input type="hidden" name="from_onboarding" value="1">
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {{-- Left Column: Editor --}}
      <div class="space-y-6">
        {{-- Basic Info Card --}}
        <div class="bg-white/5 rounded-xl border border-white/10 p-6 space-y-5">
          <h3 class="text-white font-semibold flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Información básica
          </h3>

          {{-- Name --}}
          <div>
            <label for="name" class="block text-sm font-medium text-white/80 mb-2">
              Nombre de la plantilla <span class="text-red-400">*</span>
            </label>
            <input 
              type="text" 
              id="name" 
              name="name" 
              value="{{ old('name') }}"
              required
              class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/30 transition @error('name') border-red-500/50 @enderror"
              placeholder="Ej: Recordatorio estándar"
            >
            @error('name')
              <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
          </div>

          {{-- Code --}}
          <div>
            <label for="code" class="block text-sm font-medium text-white/80 mb-2">
              Código de mensaje
              <span class="text-white/40 font-normal">(opcional)</span>
            </label>
            <input 
              type="text" 
              id="code" 
              name="code" 
              value="{{ old('code', request('code')) }}"
              maxlength="20"
              class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/30 transition uppercase @error('code') border-red-500/50 @enderror"
              placeholder="Ej: BP, RC, ST"
            >
            @error('code')
              <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-xs text-white/40">
              <span class="text-cyan-400/70">💡</span> Este código se extrae del título del evento en Google Calendar. Ej: "EVTA Cita 1 <strong class="text-cyan-300">BP</strong>"
            </p>
          </div>

          @if($channel === 'email')
          {{-- Subject (only for email) --}}
          <div>
            <label for="subject" class="block text-sm font-medium text-white/80 mb-2">
              Asunto del email <span class="text-red-400">*</span>
            </label>
            <input 
              type="text" 
              id="subject" 
              name="subject" 
              value="{{ old('subject', $defaultSubject) }}"
              required
              class="w-full px-4 py-2.5 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/30 transition @error('subject') border-red-500/50 @enderror"
              placeholder="Asunto del correo"
              data-preview-field="subject"
            >
            @error('subject')
              <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
          </div>
          @endif
        </div>

        {{-- Message Editor Card --}}
        <div class="bg-white/5 rounded-xl border border-white/10 p-6 space-y-5">
          <h3 class="text-white font-semibold flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Contenido del mensaje
          </h3>

          {{-- Insert Fields Section --}}
          <div class="space-y-3">
            <label class="block text-sm font-medium text-white/80">
              Insertar datos del paciente
            </label>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="insert-field-btn group px-3 py-2 bg-gradient-to-r from-cyan-500/10 to-blue-500/10 hover:from-cyan-500/20 hover:to-blue-500/20 border border-cyan-500/20 hover:border-cyan-500/40 text-cyan-300 rounded-lg text-sm font-medium transition-all" data-field="patient_first_name" title="Primer nombre del paciente">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-cyan-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                  Nombre
                </span>
              </button>
              <button type="button" class="insert-field-btn group px-3 py-2 bg-gradient-to-r from-cyan-500/10 to-blue-500/10 hover:from-cyan-500/20 hover:to-blue-500/20 border border-cyan-500/20 hover:border-cyan-500/40 text-cyan-300 rounded-lg text-sm font-medium transition-all" data-field="patient_name" title="Nombre completo del paciente">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-cyan-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                  Nombre completo
                </span>
              </button>
            </div>

            <label class="block text-sm font-medium text-white/80 mt-4">
              Insertar datos de la cita
            </label>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="insert-field-btn px-3 py-2 bg-gradient-to-r from-purple-500/10 to-pink-500/10 hover:from-purple-500/20 hover:to-pink-500/20 border border-purple-500/20 hover:border-purple-500/40 text-purple-300 rounded-lg text-sm font-medium transition-all" data-field="appointment_date" title="Fecha de la cita">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-purple-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                  Fecha
                </span>
              </button>
              <button type="button" class="insert-field-btn px-3 py-2 bg-gradient-to-r from-purple-500/10 to-pink-500/10 hover:from-purple-500/20 hover:to-pink-500/20 border border-purple-500/20 hover:border-purple-500/40 text-purple-300 rounded-lg text-sm font-medium transition-all" data-field="appointment_time" title="Hora de la cita">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-purple-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  Hora
                </span>
              </button>
              <button type="button" class="insert-field-btn px-3 py-2 bg-gradient-to-r from-purple-500/10 to-pink-500/10 hover:from-purple-500/20 hover:to-pink-500/20 border border-purple-500/20 hover:border-purple-500/40 text-purple-300 rounded-lg text-sm font-medium transition-all" data-field="appointment_summary" title="Título de la cita">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-purple-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  Título cita
                </span>
              </button>
              <button type="button" class="insert-field-btn px-3 py-2 bg-gradient-to-r from-purple-500/10 to-pink-500/10 hover:from-purple-500/20 hover:to-pink-500/20 border border-purple-500/20 hover:border-purple-500/40 text-purple-300 rounded-lg text-sm font-medium transition-all" data-field="professional_name" title="Tu nombre">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-purple-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  Tu nombre
                </span>
              </button>
              @if($channel === 'email')
              <button type="button" class="insert-field-btn px-3 py-2 bg-gradient-to-r from-purple-500/10 to-pink-500/10 hover:from-purple-500/20 hover:to-pink-500/20 border border-purple-500/20 hover:border-purple-500/40 text-purple-300 rounded-lg text-sm font-medium transition-all" data-field="hangout_link" title="Enlace de videollamada">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-purple-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                  Videollamada
                </span>
              </button>
              @endif
            </div>

            @if($channel === 'sms')
            {{-- SMS Links (text only) --}}
            <label class="block text-sm font-medium text-white/80 mt-4">
              Insertar enlaces
            </label>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="insert-field-btn px-3 py-2 bg-gradient-to-r from-emerald-500/10 to-green-500/10 hover:from-emerald-500/20 hover:to-green-500/20 border border-emerald-500/20 hover:border-emerald-500/40 text-emerald-300 rounded-lg text-sm font-medium transition-all" data-field="confirm_link" title="Enlace para confirmar">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-emerald-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  Link confirmar
                </span>
              </button>
              <button type="button" class="insert-field-btn px-3 py-2 bg-gradient-to-r from-red-500/10 to-orange-500/10 hover:from-red-500/20 hover:to-orange-500/20 border border-red-500/20 hover:border-red-500/40 text-red-300 rounded-lg text-sm font-medium transition-all" data-field="cancel_link" title="Enlace para cancelar">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-red-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                  Link cancelar
                </span>
              </button>
              <button type="button" class="insert-field-btn px-3 py-2 bg-gradient-to-r from-amber-500/10 to-yellow-500/10 hover:from-amber-500/20 hover:to-yellow-500/20 border border-amber-500/20 hover:border-amber-500/40 text-amber-300 rounded-lg text-sm font-medium transition-all" data-field="reschedule_link" title="Enlace WhatsApp para cambiar cita">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-amber-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                  Link cambiar
                </span>
              </button>
            </div>
            @else
            {{-- Email Buttons --}}
            <label class="block text-sm font-medium text-white/80 mt-4">
              Insertar botones de acción
              <span class="text-white/40 font-normal text-xs ml-1">(se verán como botones en el email)</span>
            </label>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="insert-button-btn px-3 py-2 bg-gradient-to-r from-emerald-500/20 to-green-500/20 hover:from-emerald-500/30 hover:to-green-500/30 border border-emerald-500/30 hover:border-emerald-500/50 text-emerald-300 rounded-lg text-sm font-medium transition-all" data-button-type="confirm" title="Botón para confirmar cita">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  ✅ Botón Confirmar
                </span>
              </button>
              <button type="button" class="insert-button-btn px-3 py-2 bg-gradient-to-r from-red-500/20 to-orange-500/20 hover:from-red-500/30 hover:to-orange-500/30 border border-red-500/30 hover:border-red-500/50 text-red-300 rounded-lg text-sm font-medium transition-all" data-button-type="cancel" title="Botón para cancelar cita">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                  ❌ Botón Cancelar
                </span>
              </button>
              <button type="button" class="insert-button-btn px-3 py-2 bg-gradient-to-r from-amber-500/20 to-yellow-500/20 hover:from-amber-500/30 hover:to-yellow-500/30 border border-amber-500/30 hover:border-amber-500/50 text-amber-300 rounded-lg text-sm font-medium transition-all" data-button-type="reschedule" title="Botón WhatsApp para cambiar cita">
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                  📅 Botón Cambiar
                </span>
              </button>
            </div>
            @endif
          </div>

          {{-- Body Textarea --}}
          <div>
            <label for="body" class="block text-sm font-medium text-white/80 mb-2">
              Tu mensaje <span class="text-red-400">*</span>
            </label>
            <textarea 
              id="body" 
              name="body" 
              rows="{{ $channel === 'sms' ? 5 : 12 }}"
              required
              class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/30 transition text-sm leading-relaxed @error('body') border-red-500/50 @enderror"
              placeholder="Escribe tu mensaje aquí... Haz clic en los botones de arriba para insertar datos automáticamente."
              data-preview-field="body"
            >{{ old('body', $defaultBody) }}</textarea>
            @error('body')
              <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror

            @if($channel === 'sms')
              {{-- SMS Character Counter --}}
              <div class="mt-3 p-3 bg-white/5 rounded-lg border border-white/10">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                      <span class="text-white/50 text-sm">Caracteres:</span>
                      <span id="char-count" class="text-white font-semibold">0</span>
                    </div>
                    <div class="w-px h-4 bg-white/20"></div>
                    <div class="flex items-center gap-1.5">
                      <span class="text-white/50 text-sm">SMS:</span>
                      <span id="sms-segments" class="text-cyan-400 font-semibold">1</span>
                    </div>
                  </div>
                  <div id="sms-warning" class="hidden text-amber-400 text-xs flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>Mensaje largo = más coste</span>
                  </div>
                </div>
                <div class="mt-2 h-1.5 bg-white/10 rounded-full overflow-hidden">
                  <div id="char-progress" class="h-full bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
              </div>
            @endif
          </div>

          {{-- Default Checkbox --}}
          <div class="border-t border-white/10 pt-5">
            <label class="flex items-start gap-3 cursor-pointer group">
              <input 
                type="checkbox" 
                name="is_default" 
                value="1"
                {{ old('is_default') ? 'checked' : '' }}
                class="mt-0.5 w-5 h-5 bg-white/5 border border-white/20 rounded text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0 cursor-pointer"
              >
              <div>
                <span class="text-white/90 group-hover:text-white transition">Usar como plantilla predeterminada</span>
                <p class="text-xs text-white/40 mt-0.5">Se usará automáticamente cuando no haya código específico en la cita</p>
              </div>
            </label>
          </div>
        </div>
      </div>

      {{-- Right Column: Live Preview --}}
      <div class="space-y-4">
        <div class="bg-white/5 rounded-xl border border-white/10 overflow-hidden sticky top-4">
          <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between gap-3">
            <div>
              <h3 class="text-white font-semibold text-sm tracking-tight">Vista previa</h3>
              <p class="text-xs text-white/40 mt-0.5">Así lo recibirá tu paciente</p>
            </div>

            @if($isEmail)
              <div class="inline-flex items-center bg-white/5 border border-white/10 rounded-md p-0.5">
                <button type="button" id="preview-desktop" data-active="true"
                        class="px-2.5 py-1 text-xs rounded text-white/80 data-[active=true]:bg-white/10 data-[active=true]:text-white transition">
                  Escritorio
                </button>
                <button type="button" id="preview-mobile" data-active="false"
                        class="px-2.5 py-1 text-xs rounded text-white/60 data-[active=true]:bg-white/10 data-[active=true]:text-white transition">
                  Móvil
                </button>
              </div>
            @endif
          </div>

          @if($isEmail)
            <div class="px-5 py-3 border-b border-white/10 bg-white/[0.03]">
              <p class="text-[11px] uppercase tracking-wider text-white/40 mb-1">Asunto</p>
              <p class="text-white/90 text-sm font-medium leading-snug truncate" id="preview-subject">&nbsp;</p>
            </div>

            <div class="p-4 bg-[#1a1a1a]/40">
              <div id="preview-frame-wrap" class="mx-auto transition-[max-width] duration-300 ease-out" style="max-width:100%;">
                <iframe id="preview-frame"
                        title="Vista previa del correo"
                        class="block w-full bg-white rounded-md border border-white/10 shadow-[0_20px_40px_-15px_rgba(0,0,0,0.5)]"
                        style="height: 640px;"></iframe>
              </div>
            </div>

            <div class="px-5 py-3 border-t border-white/10">
              <p class="text-[11px] uppercase tracking-wider text-white/40 mb-2">Variables detectadas</p>
              <div id="vars-detected" class="flex flex-wrap gap-1.5 mb-3">
                <span class="text-xs text-white/30">Ninguna todavía.</span>
              </div>
              <div id="vars-missing-wrap" class="hidden">
                <p class="text-[11px] uppercase tracking-wider text-amber-400/80 mb-2">Recomendadas que faltan</p>
                <div id="vars-missing" class="flex flex-wrap gap-1.5"></div>
                <p class="text-[11px] text-amber-300/70 mt-2 leading-relaxed">
                  Sin estos enlaces el paciente no podrá confirmar ni cancelar desde el correo.
                </p>
              </div>
            </div>

            <div class="px-5 py-3 border-t border-white/10 bg-white/[0.02]">
              <button type="button" id="send-test-btn"
                      class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-white/90 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 rounded-md transition active:translate-y-[1px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span id="send-test-label">Enviarme un correo de prueba</span>
              </button>
              <p id="send-test-feedback" class="hidden mt-2 text-xs text-center"></p>
            </div>
          @else
            <div class="p-4">
              <div class="max-w-[280px] mx-auto">
                <div class="bg-gray-900 rounded-[2rem] p-3 shadow-2xl border border-white/10">
                  <div class="bg-black h-6 rounded-t-2xl flex items-center justify-center mb-1">
                    <div class="w-16 h-4 bg-gray-900 rounded-full"></div>
                  </div>
                  <div class="bg-gray-800 rounded-2xl p-4 min-h-[200px]">
                    <div class="text-xs text-white/40 text-center mb-3">Mensaje de texto</div>
                    <div class="bg-emerald-600/90 text-white p-3 rounded-2xl rounded-tl-sm text-sm leading-relaxed shadow-lg" id="preview-body">
                      Cargando vista previa...
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="px-5 py-3 border-t border-white/10">
              <p class="text-[11px] uppercase tracking-wider text-white/40 mb-2">Variables detectadas</p>
              <div id="vars-detected" class="flex flex-wrap gap-1.5">
                <span class="text-xs text-white/30">Ninguna todavía.</span>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3 justify-end pt-4">
      <a href="{{ route('templates.index', ['channel' => $channel]) }}" class="btn bg-white/5 hover:bg-white/10 text-white border border-white/10">
        Cancelar
      </a>
      <button type="submit" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Crear plantilla
      </button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const isEmail = {{ $isEmail ? 'true' : 'false' }};
  const csrf = '{{ csrf_token() }}';
  const previewUrl = '{{ route("templates.preview") }}';
  const sendTestUrl = '{{ route("templates.sendTest") }}';

  const bodyField = document.getElementById('body');
  const subjectField = document.getElementById('subject');
  const insertFieldBtns = document.querySelectorAll('.insert-field-btn');
  const insertButtonBtns = document.querySelectorAll('.insert-button-btn');

  const previewFrame = document.getElementById('preview-frame');
  const previewFrameWrap = document.getElementById('preview-frame-wrap');
  const previewSubject = document.getElementById('preview-subject');
  const desktopBtn = document.getElementById('preview-desktop');
  const mobileBtn = document.getElementById('preview-mobile');
  const sendTestBtn = document.getElementById('send-test-btn');
  const sendTestLabel = document.getElementById('send-test-label');
  const sendTestFeedback = document.getElementById('send-test-feedback');

  const previewBody = document.getElementById('preview-body');
  const charCount = document.getElementById('char-count');
  const smsSegments = document.getElementById('sms-segments');
  const charProgress = document.getElementById('char-progress');
  const smsWarning = document.getElementById('sms-warning');

  const varsDetected = document.getElementById('vars-detected');
  const varsMissing = document.getElementById('vars-missing');
  const varsMissingWrap = document.getElementById('vars-missing-wrap');

  const recommendedForEmail = ['confirm_link', 'cancel_link'];
  const buttonMarkerImpliesVar = {
    '[BOTON_CONFIRMAR]': 'confirm_link',
    '[BOTON_CANCELAR]': 'cancel_link',
    '[BOTON_CAMBIAR]': 'reschedule_link'
  };

  const buttonTemplates = {
    'confirm': '[BOTON_CONFIRMAR]',
    'cancel': '[BOTON_CANCELAR]',
    'reschedule': '[BOTON_CAMBIAR]'
  };

  function detectVariables(text) {
    const used = new Set();
    const re = /\{\{\s*([a-z_][a-z0-9_]*)\s*\}\}/gi;
    let m;
    while ((m = re.exec(text)) !== null) used.add(m[1]);
    Object.keys(buttonMarkerImpliesVar).forEach((marker) => {
      if (text.indexOf(marker) !== -1) used.add(buttonMarkerImpliesVar[marker]);
    });
    return used;
  }

  function renderChips(used) {
    if (!varsDetected) return;
    varsDetected.innerHTML = '';
    if (used.size === 0) {
      varsDetected.innerHTML = '<span class="text-xs text-white/30">Ninguna todavía.</span>';
    } else {
      used.forEach((v) => {
        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono bg-white/5 border border-white/10 text-white/70';
        chip.textContent = '{{ "{{" }}' + v + '{{ "}}" }}';
        varsDetected.appendChild(chip);
      });
    }
    if (isEmail && varsMissingWrap && varsMissing) {
      const missing = recommendedForEmail.filter((v) => !used.has(v));
      if (missing.length === 0) {
        varsMissingWrap.classList.add('hidden');
      } else {
        varsMissing.innerHTML = '';
        missing.forEach((v) => {
          const chip = document.createElement('span');
          chip.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono bg-amber-500/10 border border-amber-500/30 text-amber-200';
          chip.textContent = '{{ "{{" }}' + v + '{{ "}}" }}';
          varsMissing.appendChild(chip);
        });
        varsMissingWrap.classList.remove('hidden');
      }
    }
  }

  function applySampleSms(text) {
    return text
      .replace(/\{\{patient_first_name\}\}/g, 'María')
      .replace(/\{\{patient_name\}\}/g, 'María García López')
      .replace(/\{\{appointment_date\}\}/g, 'lunes 27 de enero de 2026')
      .replace(/\{\{appointment_time\}\}/g, '10:00')
      .replace(/\{\{appointment_summary\}\}/g, 'Sesión de terapia')
      .replace(/\{\{professional_name\}\}/g, @json(auth()->user()->name ?? 'tu psicóloga'))
      .replace(/\{\{confirm_link\}\}/g, 'nimbus.app/c/abc')
      .replace(/\{\{cancel_link\}\}/g, 'nimbus.app/x/xyz')
      .replace(/\{\{reschedule_link\}\}/g, 'wa.me/...');
  }

  function updateSmsPreview() {
    const text = bodyField.value;
    const parsed = applySampleSms(text);
    if (previewBody) previewBody.textContent = parsed;
    const len = parsed.length;
    if (charCount) charCount.textContent = len;
    const segments = Math.ceil(len / 160) || 1;
    if (smsSegments) smsSegments.textContent = segments;
    if (charProgress) {
      const percent = Math.min((len / 160) * 100, 100);
      charProgress.style.width = percent + '%';
      if (len > 320) {
        charProgress.className = 'h-full bg-red-500 rounded-full transition-all duration-300';
        smsWarning && smsWarning.classList.remove('hidden');
      } else if (len > 160) {
        charProgress.className = 'h-full bg-amber-500 rounded-full transition-all duration-300';
        smsWarning && smsWarning.classList.remove('hidden');
      } else {
        charProgress.className = 'h-full bg-cyan-500 rounded-full transition-all duration-300';
        smsWarning && smsWarning.classList.add('hidden');
      }
    }
  }

  let previewAbort = null;
  let debounceId = null;
  function fetchEmailPreview() {
    if (debounceId) clearTimeout(debounceId);
    debounceId = setTimeout(async () => {
      if (previewAbort) previewAbort.abort();
      previewAbort = new AbortController();
      try {
        const res = await fetch(previewUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body: JSON.stringify({
            channel: 'email',
            body: bodyField.value,
            subject: subjectField ? subjectField.value : ''
          }),
          signal: previewAbort.signal,
        });
        if (!res.ok) return;
        const data = await res.json();
        if (previewFrame && data.html) previewFrame.srcdoc = data.html;
        if (previewSubject) previewSubject.textContent = data.subject || ' ';
      } catch (e) {
        if (e.name !== 'AbortError') console.warn('preview error', e);
      }
    }, 350);
  }

  function update() {
    const used = detectVariables(bodyField.value + ' ' + (subjectField ? subjectField.value : ''));
    renderChips(used);
    if (isEmail) fetchEmailPreview();
    else updateSmsPreview();
  }

  function insertAtCursor(text) {
    const pos = bodyField.selectionStart;
    const before = bodyField.value.substring(0, pos);
    const after = bodyField.value.substring(pos);
    bodyField.value = before + text + after;
    bodyField.focus();
    bodyField.setSelectionRange(pos + text.length, pos + text.length);
    update();
  }

  insertFieldBtns.forEach((btn) => btn.addEventListener('click', () => insertAtCursor('{{ "{{" }}' + btn.dataset.field + '{{ "}}" }}')));
  insertButtonBtns.forEach((btn) => btn.addEventListener('click', () => insertAtCursor('\n' + buttonTemplates[btn.dataset.buttonType] + '\n')));

  bodyField.addEventListener('input', update);
  if (subjectField) subjectField.addEventListener('input', update);

  function setDevice(mode) {
    if (!previewFrameWrap) return;
    if (mode === 'mobile') {
      previewFrameWrap.style.maxWidth = '375px';
      desktopBtn.dataset.active = 'false';
      mobileBtn.dataset.active = 'true';
    } else {
      previewFrameWrap.style.maxWidth = '100%';
      desktopBtn.dataset.active = 'true';
      mobileBtn.dataset.active = 'false';
    }
  }
  if (desktopBtn && mobileBtn) {
    desktopBtn.addEventListener('click', () => setDevice('desktop'));
    mobileBtn.addEventListener('click', () => setDevice('mobile'));
  }

  if (sendTestBtn) {
    sendTestBtn.addEventListener('click', async () => {
      sendTestBtn.disabled = true;
      const originalLabel = sendTestLabel.textContent;
      sendTestLabel.textContent = 'Enviando…';
      sendTestFeedback.classList.add('hidden');
      try {
        const res = await fetch(sendTestUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body: JSON.stringify({
            body: bodyField.value,
            subject: subjectField ? subjectField.value : '(sin asunto)',
          }),
        });
        const data = await res.json();
        sendTestFeedback.classList.remove('hidden');
        if (res.ok) {
          sendTestFeedback.className = 'mt-2 text-xs text-center text-emerald-300';
          sendTestFeedback.textContent = data.message || 'Correo enviado.';
        } else {
          sendTestFeedback.className = 'mt-2 text-xs text-center text-red-300';
          sendTestFeedback.textContent = data.error || 'No se pudo enviar.';
        }
      } catch (e) {
        sendTestFeedback.classList.remove('hidden');
        sendTestFeedback.className = 'mt-2 text-xs text-center text-red-300';
        sendTestFeedback.textContent = 'Error de red.';
      } finally {
        sendTestLabel.textContent = originalLabel;
        sendTestBtn.disabled = false;
      }
    });
  }

  update();
});
</script>
</x-app-layout>
