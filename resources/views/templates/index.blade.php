<x-app-layout>

<div class="page-container">
  {{-- Page Header --}}
  <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
    <div class="page-header mb-0">
      <h1>Plantillas de Mensajes</h1>
      <p>Personaliza los recordatorios que se envían a tus pacientes</p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('templates.create', ['channel' => $channel]) }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>Nueva plantilla</span>
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

  {{-- Channel Tabs --}}
  <div class="flex gap-2 mb-6">
    <a href="{{ route('templates.index', ['channel' => 'email']) }}" 
       class="px-4 py-2 rounded-lg font-medium transition {{ $channel === 'email' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-white/5 text-white/60 hover:bg-white/10 hover:text-white/80' }}">
      <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
      </svg>
      Email
    </a>
    <a href="{{ route('templates.index', ['channel' => 'sms']) }}" 
       class="px-4 py-2 rounded-lg font-medium transition {{ $channel === 'sms' ? 'bg-green-500/20 text-green-300 border border-green-500/30' : 'bg-white/5 text-white/60 hover:bg-white/10 hover:text-white/80' }}">
      <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
      </svg>
      SMS
    </a>
  </div>

  {{-- Templates List --}}
  @if ($templates->isEmpty())
    <div class="empty-state">
      <div class="icon">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <h3>No hay plantillas de {{ $channel === 'email' ? 'email' : 'SMS' }}</h3>
      <p>Crea tu primera plantilla para personalizar los recordatorios</p>
      <a href="{{ route('templates.create', ['channel' => $channel]) }}" class="btn btn-primary mt-4 inline-flex">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Crear primera plantilla
      </a>
    </div>
  @else
    <div class="grid grid-cols-1 gap-4">
      @foreach($templates as $template)
        <div class="bg-white/5 rounded-xl border border-white/10 p-5 hover:bg-white/[0.07] transition {{ $template->is_default ? 'ring-2 ring-cyan-500/30' : '' }}">
          <div class="flex items-start justify-between gap-4">
            {{-- Left: Name and Preview --}}
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 mb-2">
                <h3 class="text-white font-semibold text-lg">{{ $template->name }}</h3>
                @if($template->is_default)
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-500/20 text-cyan-300">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Predeterminada
                  </span>
                @endif
              </div>

              @if($template->isEmail() && $template->subject)
                <div class="text-sm text-white/60 mb-2">
                  <span class="text-white/40">Asunto:</span> {{ Str::limit($template->subject, 60) }}
                </div>
              @endif

              <div class="text-sm text-white/50 bg-white/5 rounded-lg p-3 font-mono">
                {{ Str::limit($template->body, 150) }}
              </div>

              @if($template->isSms())
                <div class="mt-2 text-xs text-white/40">
                  ~{{ strlen($template->getPreview()) }} caracteres · {{ $template->getSmsSegments() }} {{ $template->getSmsSegments() === 1 ? 'segmento' : 'segmentos' }} SMS
                </div>
              @endif
            </div>

            {{-- Right: Actions --}}
            <div class="flex flex-col gap-2">
              <a href="{{ route('templates.edit', $template) }}" class="inline-flex items-center justify-center px-3 py-1.5 bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-300 hover:text-cyan-200 rounded-lg transition text-xs font-medium">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar
              </a>

              <form action="{{ route('templates.duplicate', $template) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-1.5 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-lg transition text-xs font-medium">
                  <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                  </svg>
                  Duplicar
                </button>
              </form>

              @if(!$template->is_default)
                <form action="{{ route('templates.setDefault', $template) }}" method="POST" class="inline">
                  @csrf
                  <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-1.5 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-lg transition text-xs font-medium">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    Predeterminada
                  </button>
                </form>
              @endif

              <form action="{{ route('templates.destroy', $template) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar esta plantilla?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300 rounded-lg transition text-xs font-medium">
                  <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                  Eliminar
                </button>
              </form>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Dynamic Fields Reference --}}
  <div class="mt-8 bg-white/5 rounded-xl border border-white/10 p-6">
    <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
      <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
      </svg>
      Campos dinámicos disponibles
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
      @foreach($dynamicFields as $field => $description)
        <div class="flex items-start gap-2">
          <code class="px-2 py-0.5 bg-cyan-500/10 text-cyan-300 rounded text-xs whitespace-nowrap">{{ '{{' }}{{ $field }}{{ '}}' }}</code>
          <span class="text-white/60">{{ $description }}</span>
        </div>
      @endforeach
    </div>
  </div>
</div>
</x-app-layout>
