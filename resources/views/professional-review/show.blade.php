<x-guest-layout>
  <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-slate-900/95 p-8 text-white shadow-2xl">
    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-amber-300">Revisión de cita</p>
    <h1 class="text-2xl font-semibold">{{ $decision === 'confirm' ? 'Confirmar y avisar a la paciente' : 'Cancelar y mover al domingo' }}</h1>
    <p class="mt-3 text-sm leading-relaxed text-white/65">Esta acción solo se realizará cuando pulses el botón inferior.</p>

    <div class="mt-6 rounded-xl border border-white/10 bg-white/5 p-5 text-sm leading-7">
      <strong class="block text-base text-white">{{ $appointment->summary }}</strong>
      <span class="text-white/70">{{ $appointment->formatted_date }} a las {{ $appointment->formatted_time }}</span><br>
      <span class="text-white/70">{{ $appointment->patient?->name ?? 'Sin paciente asociado' }}</span>
    </div>

    <form method="POST" action="{{ url()->full() }}" class="mt-6">
      @csrf
      <button type="submit" class="w-full rounded-lg px-5 py-3 font-semibold text-white transition active:translate-y-px {{ $decision === 'confirm' ? 'bg-green-700 hover:bg-green-800' : 'bg-red-700 hover:bg-red-800' }}">
        {{ $decision === 'confirm' ? 'Sí, confirmar y enviar ahora' : 'Sí, cancelar y mover al domingo' }}
      </button>
    </form>
  </div>
</x-guest-layout>
