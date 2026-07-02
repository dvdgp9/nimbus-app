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

      {{-- Presets de diagnóstico --}}
      <div>
        <label class="block text-sm font-medium text-white/80 mb-2">Casos de prueba rápidos</label>
        <div class="flex flex-wrap gap-2">
          <button type="button" data-preset="control" class="btn bg-white/5 hover:bg-white/10 text-white text-xs">C · Control</button>
          <button type="button" data-preset="emoji" class="btn bg-white/5 hover:bg-white/10 text-white text-xs">A · Emojis 🤗📅✅</button>
          <button type="button" data-preset="waurl" class="btn bg-white/5 hover:bg-white/10 text-white text-xs">B · URL wa.me con %XX</button>
          <button type="button" data-preset="full" class="btn bg-white/5 hover:bg-white/10 text-white text-xs">D · Mensaje completo que falla</button>
        </div>
        <p class="mt-1 text-xs text-white/40">Pulsa un caso, pon tu número y envía. Así aislamos qué dispara el 404.</p>
      </div>

      {{-- Message --}}
      <div>
        <label for="message" class="block text-sm font-medium text-white/80 mb-2">
          Mensaje <span class="text-red-400">*</span>
        </label>
        <textarea
          id="message"
          name="message"
          rows="6"
          maxlength="2000"
          required
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('message') border-red-500/50 @enderror"
          placeholder="Escribe tu mensaje de prueba..."
        >{{ old('message', 'Hola! Este es un SMS de prueba desde Nimbus.') }}</textarea>
        @error('message')
          <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-white/40">
          <span id="char-count">0</span> caracteres ·
          Codificación: <span id="enc-info" class="text-white/70">GSM-7</span> ·
          Partes SMS estimadas: <span id="parts-info" class="text-white/70">1</span>
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
  (function () {
    const messageEl = document.getElementById('message');
    const countEl = document.getElementById('char-count');
    const encEl = document.getElementById('enc-info');
    const partsEl = document.getElementById('parts-info');

    // GSM 03.38 basic + extension chars (everything else forces Unicode/UCS-2).
    const GSM_BASIC = "@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
    const GSM_EXT = "^{}\\[~]|€";

    // Presets para el diagnóstico del 404 de Acumbamail.
    const PRESETS = {
      control: "Prueba C control. SMS normal sin nada especial desde Nimbus.",
      emoji: "Prueba A emojis 🤗📅✅ desde Nimbus.",
      waurl: "Prueba B url https://wa.me/34621072649?text=Hola%21+Me+gustar%C3%ADa+cambiar+la+cita+del+04%2F07",
      full: "Hola, Laura 🤗\n\nTe escribo para recordarte y confirmar nuestra próxima sesión:\n\n📅 Sábado 4 de julio\n🕐 08:00 (hora peninsular española)\n⏳ Duración: 55 minutos\n\n🔄 Si necesitas cancelar o reprogramar la cita, por favor avísame con más de 24 horas de antelación.\n\n¿Qué deseas hacer?\n\n✅ Confirmar: https://nimbus.wthefox.com/link/HpiVHjh4q2sxgbOOzq07qICla5Nc6nrh-56413ab5cbc2c3fba40c87a6cdf35983\n❌ Cancelar: https://nimbus.wthefox.com/link/oXsuulx483mBaTl93Fo4tGXNSW1JqXB3-06c9322ef58035d750f196443a06c8aa\n📆 Reprogramar: https://wa.me/34621072649?text=Hola%21+Me+gustar%C3%ADa+cambiar+la+cita+del+04%2F07\n\n¡Muchas gracias! 🤍",
    };

    function analyze(text) {
      const chars = Array.from(text); // respeta code points (emojis)
      let isUnicode = false;
      for (const c of chars) {
        if (GSM_BASIC.indexOf(c) === -1 && GSM_EXT.indexOf(c) === -1) { isUnicode = true; break; }
      }
      let parts;
      if (isUnicode) {
        // UCS-2: los caracteres fuera del BMP (emojis) ocupan 2 unidades.
        let units = 0;
        for (const c of chars) { units += (c.codePointAt(0) > 0xFFFF) ? 2 : 1; }
        parts = units === 0 ? 1 : (units <= 70 ? 1 : Math.ceil(units / 67));
      } else {
        let units = 0;
        for (const c of chars) { units += (GSM_EXT.indexOf(c) !== -1) ? 2 : 1; }
        parts = units === 0 ? 1 : (units <= 160 ? 1 : Math.ceil(units / 153));
      }
      return { count: chars.length, enc: isUnicode ? 'Unicode (UCS-2)' : 'GSM-7', parts };
    }

    function refresh() {
      const r = analyze(messageEl.value);
      countEl.textContent = r.count;
      encEl.textContent = r.enc;
      partsEl.textContent = r.parts;
      partsEl.className = r.parts >= 7 ? 'text-amber-400' : 'text-white/70';
    }

    messageEl.addEventListener('input', refresh);

    document.querySelectorAll('[data-preset]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        messageEl.value = PRESETS[btn.dataset.preset] || '';
        refresh();
        messageEl.focus();
      });
    });

    refresh();
  })();
</script>

</x-app-layout>
