@extends('layouts.app')

@section('title', 'Próximos eventos')

@section('content')
<div class="page-container">
  {{-- Page Header --}}
  <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
    <div class="page-header mb-0">
      <h1>Próximas 48 horas</h1>
      <p>Cuenta: <span class="text-white font-medium">{{ $account ?? 'No especificada' }}</span></p>
    </div>
    <div class="flex gap-2">
      <form method="POST" action="{{ route('events.sync') }}" class="inline">
        @csrf
        <input type="hidden" name="account" value="{{ $account }}">
        <button type="submit" class="btn btn-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          <span>Sincronizar</span>
        </button>
      </form>
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

  @error('account')
    <div class="alert alert-error mb-6">
      <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ $message }}
    </div>
  @enderror

  {{-- Events Grid --}}
  @if (empty($events))
    <div class="empty-state">
      <div class="icon">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
      <h3>No hay eventos programados</h3>
      <p>Conecta una cuenta de Google Calendar para ver tus próximas citas</p>
      <a href="{{ route('google.connect') }}" class="btn btn-primary mt-4 inline-flex">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Conectar Google Calendar
      </a>
    </div>
  @else
    <div class="grid grid-cols-1 gap-4">
      @foreach ($events as $e)
        <div class="event-card">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h3>{{ $e['summary'] ?? '(Sin título)' }}</h3>
              <div class="meta">
                <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{ $e['calendar_id'] }}
              </div>
            </div>
            @if (!empty($e['hangout_link']))
              <a href="{{ $e['hangout_link'] }}" target="_blank" class="btn btn-primary text-xs py-1 px-3">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Unirse
              </a>
            @endif
          </div>
          
          <dl>
            <div>
              <dt>Inicio</dt>
              <dd>{{ $e['start_at'] }}</dd>
            </div>
            <div>
              <dt>Fin</dt>
              <dd>{{ $e['end_at'] }}</dd>
            </div>
            <div>
              <dt>Zona horaria</dt>
              <dd>{{ $e['timezone'] ?? '—' }}</dd>
            </div>
            <div>
              <dt>Enlace Meet</dt>
              <dd class="truncate">{{ $e['hangout_link'] ? 'Disponible' : '—' }}</dd>
            </div>
          </dl>

          @if (!empty($e['description']))
            <details class="mt-4">
              <summary class="cursor-pointer text-sm text-white/60 hover:text-white/80 transition select-none">
                Ver descripción
              </summary>
              <div class="mt-3 text-sm text-white/70 bg-white/5 rounded-lg p-3">
                <pre class="whitespace-pre-wrap font-sans">{{ $e['description'] }}</pre>
              </div>
            </details>
          @endif
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection
