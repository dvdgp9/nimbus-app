<div class="rounded-lg border px-3 py-2 {{ $channelActive ? ($resolution->isReady() ? 'border-emerald-500/25 bg-emerald-500/10' : 'border-amber-500/30 bg-amber-500/10') : 'border-white/10 bg-white/5' }}">
  <div class="text-xs font-semibold uppercase tracking-wide {{ $channelActive ? ($resolution->isReady() ? 'text-emerald-300' : 'text-amber-300') : 'text-white/45' }}">
    {{ $channel }}
  </div>

  @if(!$channelActive)
    <div class="mt-1 text-xs text-white/55">Canal no activo para este paciente.</div>
  @elseif($resolution->isReady())
    <div class="mt-1 text-sm text-white/90">{{ $resolution->template->name }}</div>
    <div class="mt-1 text-xs text-emerald-200/70">Lista para enviar</div>
  @else
    <div class="mt-1 text-sm font-medium text-amber-200">Recordatorio bloqueado</div>
    <div class="mt-1 text-xs text-amber-100/70">{{ $resolution->message }}</div>
  @endif
</div>
