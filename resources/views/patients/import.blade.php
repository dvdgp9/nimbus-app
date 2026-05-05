<x-app-layout>

<div class="page-container max-w-4xl">
  <div class="mb-6">
    <a href="{{ route('patients.index') }}" class="text-cyan-400 hover:text-cyan-300 transition inline-flex items-center gap-1 mb-4">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
      </svg>
      Volver a pacientes
    </a>
    <div class="page-header">
      <h1>Importar pacientes</h1>
      <p>Sube un CSV o pega tus datos desde Excel / Google Sheets. Los duplicados (por código, email o teléfono) se ignoran.</p>
    </div>
  </div>

  @if(session('status'))
    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 mb-6">
      <p class="text-emerald-300">{{ session('status') }}</p>
    </div>
  @endif

  @if(session('import_duplicates') && count(session('import_duplicates')) > 0)
    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 mb-6">
      <p class="text-amber-300 font-semibold mb-2">Duplicados ignorados:</p>
      <ul class="text-amber-300/80 text-sm space-y-1 max-h-40 overflow-y-auto">
        @foreach(session('import_duplicates') as $dup)
          <li>{{ $dup }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
      <p class="text-red-300 font-semibold mb-2">Errores durante la importación:</p>
      <ul class="text-red-300/80 text-sm space-y-1 max-h-40 overflow-y-auto">
        @foreach(session('import_errors') as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-white/5 rounded-xl border border-white/10 p-6" x-data="{ tab: 'csv' }">
    <div class="flex gap-2 mb-6">
      <button type="button" @click="tab = 'csv'" :class="tab === 'csv' ? 'bg-cyan-500/20 text-cyan-300' : 'bg-white/5 text-white/60'" class="px-3 py-1.5 rounded-lg text-sm transition">Subir CSV</button>
      <button type="button" @click="tab = 'paste'" :class="tab === 'paste' ? 'bg-cyan-500/20 text-cyan-300' : 'bg-white/5 text-white/60'" class="px-3 py-1.5 rounded-lg text-sm transition">Pegar desde Excel/Sheets</button>
    </div>

    <div x-show="tab === 'csv'">
      <form action="{{ route('patients.import.csv') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
          <label class="block text-sm text-white/60 mb-2">Formato esperado (cabecera opcional):</label>
          <code class="block bg-black/30 rounded-lg p-3 text-xs text-cyan-300 font-mono">
            código,nombre,email,teléfono<br>
            ABC,María García,maria@email.com,612345678<br>
            XYZ,Juan López,juan@email.com,+34698765432
          </code>
          <p class="text-white/50 text-xs mt-2">Se normalizan teléfonos al formato internacional (ej: 612345678 → +34612345678).</p>
        </div>

        <div class="mb-4">
          <input type="file" name="csv_file" accept=".csv,.txt"
            class="w-full text-white/60 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-cyan-500/20 file:text-cyan-300 hover:file:bg-cyan-500/30 file:cursor-pointer">
          @error('csv_file')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
          @enderror
        </div>

        <button type="submit" class="btn btn-primary">Importar CSV</button>
      </form>
    </div>

    <div x-show="tab === 'paste'" x-cloak>
      <form action="{{ route('patients.import.paste') }}" method="POST">
        @csrf
        <p class="text-white/60 text-sm mb-2">Copia desde Excel o Google Sheets y pega aquí. Columnas esperadas: código, nombre, email, teléfono. Se detecta el separador automáticamente.</p>
        <textarea name="paste" rows="10" placeholder="código	nombre	email	teléfono&#10;ABC	María García	maria@email.com	612345678"
          class="w-full font-mono text-xs bg-black/30 border border-white/10 rounded-lg p-3 text-white/80 placeholder-white/30 focus:outline-none focus:border-cyan-500/50"></textarea>
        @error('paste')
          <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
        <button type="submit" class="mt-3 btn btn-primary">Importar pegado</button>
      </form>
    </div>
  </div>
</div>
</x-app-layout>
