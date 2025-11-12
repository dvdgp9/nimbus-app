@extends('layouts.app')

@section('title', 'Pacientes')

@section('content')
<div class="page-container">
  {{-- Page Header --}}
  <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
    <div class="page-header mb-0">
      <h1>Pacientes</h1>
      <p>Gestiona tus pacientes y sus datos de contacto</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('patients.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>Nuevo paciente</span>
      </a>
    </div>
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

  {{-- Search Bar --}}
  <div class="mb-6">
    <form method="GET" action="{{ route('patients.index') }}" class="flex gap-2">
      <div class="flex-1">
        <input 
          type="text" 
          name="search" 
          value="{{ $search }}"
          placeholder="Buscar por código, nombre, email o teléfono..."
          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-cyan-500/50 transition"
        >
      </div>
      <button type="submit" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        Buscar
      </button>
      @if($search)
        <a href="{{ route('patients.index') }}" class="btn bg-white/5 hover:bg-white/10 text-white">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          Limpiar
        </a>
      @endif
    </form>
  </div>

  {{-- Patients List --}}
  @if ($patients->isEmpty())
    <div class="empty-state">
      <div class="icon">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
      </div>
      <h3>{{ $search ? 'No se encontraron pacientes' : 'No hay pacientes registrados' }}</h3>
      <p>{{ $search ? 'Intenta con otros términos de búsqueda' : 'Crea tu primer paciente para empezar' }}</p>
      @if(!$search)
        <a href="{{ route('patients.create') }}" class="btn btn-primary mt-4 inline-flex">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Crear primer paciente
        </a>
      @endif
    </div>
  @else
    <div class="bg-white/5 rounded-xl border border-white/10 overflow-hidden">
      <table class="w-full">
        <thead class="bg-white/5 border-b border-white/10">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-white/60 uppercase tracking-wider">Código</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white/60 uppercase tracking-wider">Nombre</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white/60 uppercase tracking-wider">Contacto</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white/60 uppercase tracking-wider">Canal</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white/60 uppercase tracking-wider">Consentimiento</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white/60 uppercase tracking-wider">Citas</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-white/60 uppercase tracking-wider">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/10">
          @foreach($patients as $patient)
            <tr class="hover:bg-white/5 transition">
              <td class="px-4 py-3">
                <span class="font-mono font-bold text-cyan-400">{{ $patient->code }}</span>
              </td>
              <td class="px-4 py-3">
                <div class="text-white font-medium">{{ $patient->name }}</div>
              </td>
              <td class="px-4 py-3 text-sm">
                @if($patient->email)
                  <div class="text-white/70">{{ $patient->email }}</div>
                @endif
                @if($patient->phone)
                  <div class="text-white/60">{{ $patient->phone }}</div>
                @endif
                @if(!$patient->email && !$patient->phone)
                  <span class="text-white/40">Sin contacto</span>
                @endif
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                  {{ $patient->preferred_channel === 'email' ? 'bg-blue-500/20 text-blue-400' : '' }}
                  {{ $patient->preferred_channel === 'sms' ? 'bg-green-500/20 text-green-400' : '' }}
                  {{ $patient->preferred_channel === 'whatsapp' ? 'bg-emerald-500/20 text-emerald-400' : '' }}">
                  {{ ucfirst($patient->preferred_channel) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-1">
                  <span class="inline-flex items-center justify-center w-6 h-6 rounded {{ $patient->consent_email ? 'bg-green-500/20 text-green-400' : 'bg-white/5 text-white/30' }}" title="Email">
                    @if($patient->consent_email)
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    @else
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    @endif
                  </span>
                  <span class="inline-flex items-center justify-center w-6 h-6 rounded {{ $patient->consent_sms ? 'bg-green-500/20 text-green-400' : 'bg-white/5 text-white/30' }}" title="SMS">
                    @if($patient->consent_sms)
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    @else
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    @endif
                  </span>
                  <span class="inline-flex items-center justify-center w-6 h-6 rounded {{ $patient->consent_whatsapp ? 'bg-green-500/20 text-green-400' : 'bg-white/5 text-white/30' }}" title="WhatsApp">
                    @if($patient->consent_whatsapp)
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    @else
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    @endif
                  </span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="text-white/70">{{ $patient->appointments_count }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-2">
                  <a href="{{ route('patients.show', $patient) }}" class="text-cyan-400 hover:text-cyan-300 transition" title="Ver detalles">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                  </a>
                  <a href="{{ route('patients.edit', $patient) }}" class="text-white/60 hover:text-white transition" title="Editar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($patients->hasPages())
      <div class="mt-6">
        {{ $patients->links() }}
      </div>
    @endif
  @endif
</div>
@endsection
