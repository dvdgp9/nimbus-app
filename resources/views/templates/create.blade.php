<x-app-layout>

<div class="page-container max-w-5xl">
  {{-- Page Header --}}
  <div class="mb-8">
    <a href="{{ route('templates.index', ['channel' => $channel]) }}" class="text-cyan-400 hover:text-cyan-300 transition inline-flex items-center gap-1 mb-4">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
      </svg>
      Volver a plantillas
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
              value="{{ old('code') }}"
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
          <div class="px-6 py-4 border-b border-white/10 bg-white/5">
            <h3 class="text-white font-semibold flex items-center gap-2">
              <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
              Vista previa
              <span class="text-xs text-white/40 font-normal ml-auto">Así lo verá tu paciente</span>
            </h3>
          </div>

          @if($channel === 'email')
            {{-- Email Preview --}}
            <div class="p-4">
              <div class="bg-[#0b1020] rounded-lg overflow-hidden border border-white/10 shadow-xl">
                {{-- Email Header --}}
                <div class="bg-gradient-to-r from-cyan-500 to-blue-500 px-5 py-4 text-center">
                  <div class="text-white text-xl font-bold flex items-center justify-center gap-2">
                    <span>☁️</span>
                    <span>Nimbus</span>
                  </div>
                  <div class="text-white/80 text-xs uppercase tracking-wider mt-1">Recordatorio de Cita</div>
                </div>
                {{-- Email Subject --}}
                <div class="px-5 py-3 border-b border-white/10 bg-white/5">
                  <span class="text-white/40 text-xs">Asunto:</span>
                  <span class="text-white/90 text-sm ml-2" id="preview-subject">Recordatorio: Sesión de terapia</span>
                </div>
                {{-- Email Body --}}
                <div class="px-5 py-5 text-sm text-white/85 leading-relaxed" id="preview-body">
                  Cargando vista previa...
                </div>
                {{-- Email Footer --}}
                <div class="px-5 py-3 border-t border-white/10 bg-black/20 text-center">
                  <p class="text-white/30 text-xs">Este correo fue enviado automáticamente por Nimbus</p>
                </div>
              </div>
            </div>
          @else
            {{-- SMS Preview --}}
            <div class="p-4">
              <div class="max-w-[280px] mx-auto">
                {{-- Phone Frame --}}
                <div class="bg-gray-900 rounded-[2rem] p-3 shadow-2xl border border-white/10">
                  {{-- Phone Notch --}}
                  <div class="bg-black h-6 rounded-t-2xl flex items-center justify-center mb-1">
                    <div class="w-16 h-4 bg-gray-900 rounded-full"></div>
                  </div>
                  {{-- SMS Bubble --}}
                  <div class="bg-gray-800 rounded-2xl p-4 min-h-[200px]">
                    <div class="text-xs text-white/40 text-center mb-3">Mensaje de texto</div>
                    <div class="bg-emerald-600/90 text-white p-3 rounded-2xl rounded-tl-sm text-sm leading-relaxed shadow-lg" id="preview-body">
                      Cargando vista previa...
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif

          <div class="px-6 py-3 border-t border-white/10 bg-white/5">
            <p class="text-xs text-white/40 text-center">
              💡 Los datos se rellenan automáticamente con información real de cada paciente
            </p>
          </div>
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
document.addEventListener('DOMContentLoaded', function() {
  const bodyField = document.getElementById('body');
  const subjectField = document.getElementById('subject');
  const previewBody = document.getElementById('preview-body');
  const previewSubject = document.getElementById('preview-subject');
  const charCount = document.getElementById('char-count');
  const smsSegments = document.getElementById('sms-segments');
  const charProgress = document.getElementById('char-progress');
  const smsWarning = document.getElementById('sms-warning');
  const insertFieldBtns = document.querySelectorAll('.insert-field-btn');
  const insertButtonBtns = document.querySelectorAll('.insert-button-btn');
  const isEmail = {{ $channel === 'email' ? 'true' : 'false' }};

  // Sample data for preview
  const sampleData = {
    'patient_name': 'María García López',
    'patient_first_name': 'María',
    'patient_email': 'maria@ejemplo.com',
    'appointment_date': 'Lunes 27 de Enero de 2026',
    'appointment_time': '10:00',
    'appointment_summary': 'Sesión de terapia',
    'professional_name': '{{ auth()->user()->name ?? "Dr. Juan Pérez" }}',
    'confirm_link': '{{ url("/link/abc123") }}',
    'cancel_link': '{{ url("/link/xyz789") }}',
    'reschedule_link': 'https://wa.me/34621072649?text=Cambiar%20cita',
    'hangout_link': 'https://meet.google.com/abc-defg-hij',
  };

  // Button templates for email
  const buttonTemplates = {
    'confirm': '[BOTON_CONFIRMAR]',
    'cancel': '[BOTON_CANCELAR]',
    'reschedule': '[BOTON_CAMBIAR]'
  };

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function parseTemplate(text) {
    let result = text;
    for (const [field, value] of Object.entries(sampleData)) {
      result = result.replace(new RegExp('\\{\\{' + field + '\\}\\}', 'g'), value);
    }
    return result;
  }

  function renderEmailPreview(text) {
    let html = escapeHtml(parseTemplate(text));
    
    // Convert button markers to actual buttons
    html = html.replace(/\[BOTON_CONFIRMAR\]/g, 
      '<a href="#" style="display:inline-block;padding:12px 24px;background:linear-gradient(135deg,#10b981,#059669);color:white;text-decoration:none;border-radius:8px;font-weight:600;margin:8px 4px;">✅ Confirmar cita</a>');
    html = html.replace(/\[BOTON_CANCELAR\]/g, 
      '<a href="#" style="display:inline-block;padding:12px 24px;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;text-decoration:none;border-radius:8px;font-weight:600;margin:8px 4px;">❌ Cancelar cita</a>');
    html = html.replace(/\[BOTON_CAMBIAR\]/g, 
      '<a href="#" style="display:inline-block;padding:12px 24px;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;text-decoration:none;border-radius:8px;font-weight:600;margin:8px 4px;">📅 Cambiar cita</a>');
    
    // Convert newlines to br
    html = html.replace(/\n/g, '<br>');
    
    return html;
  }

  function updatePreview() {
    const bodyText = bodyField.value;
    
    if (isEmail) {
      previewBody.innerHTML = renderEmailPreview(bodyText);
      if (subjectField) {
        previewSubject.textContent = parseTemplate(subjectField.value);
      }
    } else {
      // SMS preview - plain text
      previewBody.textContent = parseTemplate(bodyText);
    }

    // Update char count for SMS
    if (charCount) {
      const parsedLength = parseTemplate(bodyText).length;
      charCount.textContent = parsedLength;
      const segments = Math.ceil(parsedLength / 160) || 1;
      smsSegments.textContent = segments;
      
      // Update progress bar
      const percent = Math.min((parsedLength / 160) * 100, 100);
      charProgress.style.width = percent + '%';
      
      // Color based on length
      if (parsedLength > 320) {
        charProgress.className = 'h-full bg-gradient-to-r from-red-500 to-orange-500 rounded-full transition-all duration-300';
        smsWarning.classList.remove('hidden');
      } else if (parsedLength > 160) {
        charProgress.className = 'h-full bg-gradient-to-r from-amber-500 to-yellow-500 rounded-full transition-all duration-300';
        smsWarning.classList.remove('hidden');
      } else {
        charProgress.className = 'h-full bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full transition-all duration-300';
        smsWarning.classList.add('hidden');
      }
    }
  }

  // Insert dynamic field at cursor position
  function insertAtCursor(text) {
    const cursorPos = bodyField.selectionStart;
    const textBefore = bodyField.value.substring(0, cursorPos);
    const textAfter = bodyField.value.substring(cursorPos);
    
    bodyField.value = textBefore + text + textAfter;
    bodyField.focus();
    bodyField.setSelectionRange(cursorPos + text.length, cursorPos + text.length);
    updatePreview();
  }

  // Field buttons
  insertFieldBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const field = '{{' + this.dataset.field + '}}';
      insertAtCursor(field);
    });
  });

  // Button buttons (email only)
  insertButtonBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const buttonType = this.dataset.buttonType;
      const marker = buttonTemplates[buttonType];
      insertAtCursor('\n' + marker + '\n');
    });
  });

  // Listen for input changes
  bodyField.addEventListener('input', updatePreview);
  if (subjectField) {
    subjectField.addEventListener('input', updatePreview);
  }

  // Initial preview
  updatePreview();
});
</script>
</x-app-layout>
