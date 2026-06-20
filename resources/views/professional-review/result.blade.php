<x-guest-layout>
  <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-slate-900/95 p-8 text-center text-white shadow-2xl">
    <h1 class="text-2xl font-semibold">{{ $success ? 'Acción completada' : 'No se pudo completar' }}</h1>
    <p class="mt-4 leading-relaxed text-white/65">
      @if($success && $decision === 'confirm')
        La cita quedó confirmada y Nimbus procesó el envío inmediato a la paciente.
      @elseif($success)
        La cita quedó cancelada y se movió al domingo.
      @else
        La cita ya fue revisada o se produjo un problema al ejecutar la acción. Revisa Nimbus antes de intentarlo de nuevo.
      @endif
    </p>
  </div>
</x-guest-layout>
