@extends('layouts.app')

@section('title', 'Editar Paciente')

@section('content')
<div class="page-container max-w-3xl">
  {{-- Page Header --}}
  <div class="mb-8">
    <a href="{{ route('patients.show', $patient) }}" class="text-cyan-400 hover:text-cyan-300 transition inline-flex items-center gap-1 mb-4">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
      </svg>
      Volver al paciente
    </a>
    <div class="page-header">
      <h1>Editar Paciente</h1>
      <p>Actualiza los datos de <strong>{{ $patient->code }}</strong> - {{ $patient->name }}</p>
    </div>
  </div>

  {{-- Form --}}
  <form method="POST" action="{{ route('patients.update', $patient) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="bg-white/5 rounded-xl border border-white/10 p-6 space-y-6">
      {{-- Code --}}
      <div>
        <label for="code" class="block text-sm font-medium text-white/80 mb-2">
          Código <span class="text-red-400">*</span>
        </label>
        <input 
          type="text" 
          id="code" 
          name="code" 
          value="{{ old('code', $patient->code) }}"
          required
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('code') border-red-500/50 @enderror"
          placeholder="Ej: P123, ABC, 001"
        >
        @error('code')
          <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-white/40">Se convertirá automáticamente a mayúsculas</p>
      </div>

      {{-- Name --}}
      <div>
        <label for="name" class="block text-sm font-medium text-white/80 mb-2">
          Nombre completo <span class="text-red-400">*</span>
        </label>
        <input 
          type="text" 
          id="name" 
          name="name" 
          value="{{ old('name', $patient->name) }}"
          required
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('name') border-red-500/50 @enderror"
          placeholder="Nombre del paciente"
        >
        @error('name')
          <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Email --}}
      <div>
        <label for="email" class="block text-sm font-medium text-white/80 mb-2">
          Email
        </label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          value="{{ old('email', $patient->email) }}"
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('email') border-red-500/50 @enderror"
          placeholder="correo@ejemplo.com"
        >
        @error('email')
          <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Phone --}}
      <div>
        <label for="phone" class="block text-sm font-medium text-white/80 mb-2">
          Teléfono
        </label>
        <input 
          type="text" 
          id="phone" 
          name="phone" 
          value="{{ old('phone', $patient->phone) }}"
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('phone') border-red-500/50 @enderror"
          placeholder="+34XXXXXXXXX"
        >
        @error('phone')
          <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-white/40">Formato internacional recomendado: +34XXXXXXXXX</p>
      </div>

      {{-- Preferred Channel --}}
      <div>
        <label for="preferred_channel" class="block text-sm font-medium text-white/80 mb-2">
          Canal preferido <span class="text-red-400">*</span>
        </label>
        <select 
          id="preferred_channel" 
          name="preferred_channel" 
          required
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:border-cyan-500/50 transition @error('preferred_channel') border-red-500/50 @enderror"
        >
          <option value="email" {{ old('preferred_channel', $patient->preferred_channel) === 'email' ? 'selected' : '' }}>Email</option>
          <option value="sms" {{ old('preferred_channel', $patient->preferred_channel) === 'sms' ? 'selected' : '' }}>SMS</option>
          <option value="whatsapp" {{ old('preferred_channel', $patient->preferred_channel) === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
        </select>
        @error('preferred_channel')
          <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Consent Section --}}
      <div class="border-t border-white/10 pt-6">
        <h3 class="text-white font-semibold mb-2">Consentimientos</h3>
        @if($patient->consent_date)
          <p class="text-xs text-white/40 mb-4">Consentimiento dado el {{ $patient->consent_date->format('d/m/Y') }}</p>
        @endif
        <div class="space-y-3">
          <label class="flex items-center gap-3 cursor-pointer">
            <input 
              type="checkbox" 
              name="consent_email" 
              value="1"
              {{ old('consent_email', $patient->consent_email) ? 'checked' : '' }}
              class="w-4 h-4 bg-white/5 border border-white/20 rounded text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0"
            >
            <span class="text-white/80">Consentimiento para envío de emails</span>
          </label>

          <label class="flex items-center gap-3 cursor-pointer">
            <input 
              type="checkbox" 
              name="consent_sms" 
              value="1"
              {{ old('consent_sms', $patient->consent_sms) ? 'checked' : '' }}
              class="w-4 h-4 bg-white/5 border border-white/20 rounded text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0"
            >
            <span class="text-white/80">Consentimiento para envío de SMS</span>
          </label>

          <label class="flex items-center gap-3 cursor-pointer">
            <input 
              type="checkbox" 
              name="consent_whatsapp" 
              value="1"
              {{ old('consent_whatsapp', $patient->consent_whatsapp) ? 'checked' : '' }}
              class="w-4 h-4 bg-white/5 border border-white/20 rounded text-cyan-500 focus:ring-cyan-500 focus:ring-offset-0"
            >
            <span class="text-white/80">Consentimiento para envío por WhatsApp</span>
          </label>
        </div>
      </div>

      {{-- Notes --}}
      <div>
        <label for="notes" class="block text-sm font-medium text-white/80 mb-2">
          Notas
        </label>
        <textarea 
          id="notes" 
          name="notes" 
          rows="3"
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition @error('notes') border-red-500/50 @enderror"
          placeholder="Notas adicionales sobre el paciente..."
        >{{ old('notes', $patient->notes) }}</textarea>
        @error('notes')
          <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
      </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3 justify-end">
      <a href="{{ route('patients.show', $patient) }}" class="btn bg-white/5 hover:bg-white/10 text-white">
        Cancelar
      </a>
      <button type="submit" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Guardar cambios
      </button>
    </div>
  </form>
</div>
@endsection
