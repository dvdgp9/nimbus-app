<x-app-layout>

<div class="page-container max-w-4xl">
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
        <div class="bg-white/5 rounded-xl border border-white/10 p-6 space-y-6">
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
              class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('name') border-red-500/50 @enderror"
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
            </label>
            <input 
              type="text" 
              id="code" 
              name="code" 
              value="{{ old('code') }}"
              maxlength="20"
              class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition uppercase @error('code') border-red-500/50 @enderror"
              placeholder="Ej: BP, RC, ST"
            >
            @error('code')
              <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-white/40">Este código se extrae del título del evento en Google Calendar (última palabra). Ej: "EVTA Cita 1 <strong>BP</strong>"</p>
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
              class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('subject') border-red-500/50 @enderror"
              placeholder="Asunto del correo"
              data-preview-field="subject"
            >
            @error('subject')
              <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
          </div>
          @endif

          {{-- Dynamic Fields Chips --}}
          <div>
            <label class="block text-sm font-medium text-white/80 mb-2">
              Campos dinámicos
            </label>
            <div class="flex flex-wrap gap-2" id="dynamic-fields">
              @foreach($dynamicFields as $field => $description)
                <button 
                  type="button"
                  class="dynamic-field-btn px-3 py-1.5 bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-300 rounded-lg text-xs font-medium transition"
                  data-field="{{ $field }}"
                  title="{{ $description }}"
                >
                  {!! '&#123;&#123;' . e($field) . '&#125;&#125;' !!}
                </button>
              @endforeach
            </div>
            <p class="mt-2 text-xs text-white/40">Haz clic en un campo para insertarlo en el mensaje</p>
          </div>

          {{-- Body --}}
          <div>
            <label for="body" class="block text-sm font-medium text-white/80 mb-2">
              Cuerpo del mensaje <span class="text-red-400">*</span>
            </label>
            <textarea 
              id="body" 
              name="body" 
              rows="{{ $channel === 'sms' ? 4 : 10 }}"
              required
              class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition font-mono text-sm @error('body') border-red-500/50 @enderror"
              placeholder="Escribe tu mensaje aquí..."
              data-preview-field="body"
          >{{ old('body', $defaultBody) }}</textarea>
            @error('body')
              <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror

            @if($channel === 'sms')
              <div class="mt-2 flex items-center gap-4 text-xs">
                <span class="text-white/40">
                  <span id="char-count">0</span> caracteres
                </span>
                <span class="text-white/40">
                  <span id="sms-segments">1</span> segmento(s) SMS
                </span>
              </div>
            @endif
          </div>

          {{-- Default Checkbox --}}
          <div class="border-t border-white/10 pt-4">
            <label class="flex items-center gap-3 cursor-pointer">
              <input 
                type="checkbox" 
                name="is_default" 
                value="1"
                {{ old('is_default') ? 'checked' : '' }}
                class="w-4 h-4 bg-white/5 border border-white/20 rounded text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0"
              >
              <div>
                <span class="text-white/80">Establecer como plantilla predeterminada</span>
                <p class="text-xs text-white/40">Se usará automáticamente para los recordatorios de {{ $channel === 'email' ? 'email' : 'SMS' }}</p>
              </div>
            </label>
          </div>
        </div>
      </div>

      {{-- Right Column: Live Preview --}}
      <div class="space-y-4">
        <div class="bg-white/5 rounded-xl border border-white/10 p-6 sticky top-4">
          <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            Vista previa en vivo
          </h3>

          @if($channel === 'email')
            <div class="mb-3 text-sm">
              <span class="text-white/40">Asunto:</span>
              <span class="text-white/80" id="preview-subject">Recordatorio: Sesión de terapia</span>
            </div>
          @endif

          <div class="bg-white/5 rounded-lg p-4 text-sm text-white/80 whitespace-pre-wrap min-h-[200px]" id="preview-body">
            Cargando vista previa...
          </div>

          <p class="mt-3 text-xs text-white/40">
            Los campos dinámicos se reemplazan con datos de ejemplo
          </p>
        </div>
      </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3 justify-end">
      <a href="{{ route('templates.index', ['channel' => $channel]) }}" class="btn bg-white/5 hover:bg-white/10 text-white">
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
  const dynamicButtons = document.querySelectorAll('.dynamic-field-btn');

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
    'hangout_link': 'https://meet.google.com/abc-defg-hij',
  };

  function parseTemplate(text) {
    let result = text;
    for (const [field, value] of Object.entries(sampleData)) {
      result = result.replace(new RegExp('\\{\\{' + field + '\\}\\}', 'g'), value);
    }
    return result;
  }

  function updatePreview() {
    const bodyText = bodyField.value;
    previewBody.textContent = parseTemplate(bodyText);

    if (subjectField) {
      previewSubject.textContent = parseTemplate(subjectField.value);
    }

    // Update char count for SMS
    if (charCount) {
      const parsedLength = parseTemplate(bodyText).length;
      charCount.textContent = parsedLength;
      smsSegments.textContent = Math.ceil(parsedLength / 160) || 1;
    }
  }

  // Insert dynamic field at cursor position
  dynamicButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      const field = '{{' + this.dataset.field + '}}';
      const cursorPos = bodyField.selectionStart;
      const textBefore = bodyField.value.substring(0, cursorPos);
      const textAfter = bodyField.value.substring(cursorPos);
      
      bodyField.value = textBefore + field + textAfter;
      bodyField.focus();
      bodyField.setSelectionRange(cursorPos + field.length, cursorPos + field.length);
      updatePreview();
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
