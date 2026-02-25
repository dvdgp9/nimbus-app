<x-app-layout>
<div class="min-h-screen flex items-center justify-center p-4">
  <div class="max-w-4xl w-full">
    {{-- Progress indicator --}}
    <div class="mb-8">
      <div class="flex items-center justify-center gap-2">
        @for($i = 1; $i <= 5; $i++)
          <div class="w-3 h-3 rounded-full {{ $i <= 2 ? 'bg-cyan-500' : 'bg-white/20' }}"></div>
        @endfor
      </div>
      <p class="text-center text-white/40 text-sm mt-2">Paso 2 de 5</p>
    </div>

    {{-- Main Card --}}
    <div class="bg-white/5 rounded-2xl border border-white/10 p-8">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-lg mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">Importa tus pacientes</h1>
        <p class="text-white/60">Añade tus pacientes para que Nimbus los reconozca en tu calendario</p>
      </div>

      @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 mb-6">
          <p class="text-emerald-300">{{ session('success') }}</p>
        </div>
      @endif

      @if(session('import_errors'))
        <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
          <p class="text-red-300 font-semibold mb-2">Errores durante la importación:</p>
          <ul class="text-red-300/80 text-sm space-y-1">
            @foreach(session('import_errors') as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="grid md:grid-cols-2 gap-6">
        {{-- CSV Import --}}
        <div class="bg-white/5 rounded-xl p-6 border border-white/10">
          <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Importar desde CSV
          </h3>

          <form action="{{ route('onboarding.import-csv') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
              <label class="block text-sm text-white/60 mb-2">Formato del archivo:</label>
              <code class="block bg-black/30 rounded-lg p-3 text-xs text-cyan-300 font-mono">
                código,nombre,email,teléfono<br>
                ABC,María García,maria@email.com,612345678<br>
                XYZ,Juan López,juan@email.com,698765432
              </code>
            </div>

            <div class="mb-4">
              <input 
                type="file" 
                name="csv_file" 
                accept=".csv,.txt"
                class="w-full text-white/60 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-cyan-500/20 file:text-cyan-300 hover:file:bg-cyan-500/30 file:cursor-pointer"
              >
              @error('csv_file')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
              @enderror
            </div>

            <button type="submit" class="btn bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 w-full">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
              </svg>
              Importar CSV
            </button>
          </form>
        </div>

        {{-- Manual Add --}}
        <div class="bg-white/5 rounded-xl p-6 border border-white/10">
          <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            Añadir manualmente
          </h3>

          <form action="{{ route('onboarding.add-patient') }}" method="POST">
            @csrf
            <div class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <input type="text" name="code" placeholder="Código *" required maxlength="20"
                    class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 text-sm focus:outline-none focus:border-cyan-500/50 uppercase">
                </div>
                <div>
                  <input type="text" name="name" placeholder="Nombre completo *" required
                    class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 text-sm focus:outline-none focus:border-cyan-500/50">
                </div>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <input type="email" name="email" placeholder="Email"
                    class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 text-sm focus:outline-none focus:border-cyan-500/50">
                </div>
                <div>
                  <input type="tel" name="phone" placeholder="Teléfono"
                    class="w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 text-sm focus:outline-none focus:border-cyan-500/50">
                </div>
              </div>
              @error('code')
                <p class="text-red-400 text-sm">{{ $message }}</p>
              @enderror
              <button type="submit" class="btn bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 w-full">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Añadir paciente
              </button>
            </div>
          </form>
        </div>
      </div>

      {{-- Current Patients List --}}
      @if($patients->count() > 0)
        <div class="mt-8">
          <h3 class="text-white font-semibold mb-4">
            Pacientes añadidos ({{ $patients->count() }})
          </h3>
          <div class="bg-white/5 rounded-xl border border-white/10 overflow-hidden">
            <div class="max-h-64 overflow-y-auto">
              <table class="w-full">
                <thead class="bg-white/5 sticky top-0">
                  <tr>
                    <th class="text-left text-white/60 text-xs font-medium px-4 py-2">Código</th>
                    <th class="text-left text-white/60 text-xs font-medium px-4 py-2">Nombre</th>
                    <th class="text-left text-white/60 text-xs font-medium px-4 py-2">Email</th>
                    <th class="text-left text-white/60 text-xs font-medium px-4 py-2">Teléfono</th>
                    <th class="text-right text-white/60 text-xs font-medium px-4 py-2"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                  @foreach($patients as $patient)
                    <tr class="hover:bg-white/5">
                      <td class="px-4 py-2 text-cyan-400 font-mono text-sm">{{ $patient->code }}</td>
                      <td class="px-4 py-2 text-white text-sm">{{ $patient->name }}</td>
                      <td class="px-4 py-2 text-white/60 text-sm">{{ $patient->email ?: '-' }}</td>
                      <td class="px-4 py-2 text-white/60 text-sm">{{ $patient->phone ?: '-' }}</td>
                      <td class="px-4 py-2 text-right">
                        <form action="{{ route('onboarding.delete-patient', $patient) }}" method="POST" class="inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="text-red-400/60 hover:text-red-400 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                          </button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @else
        <div class="mt-8 text-center py-8 bg-white/5 rounded-xl border border-dashed border-white/20">
          <svg class="w-12 h-12 text-white/20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          <p class="text-white/40">Aún no has añadido ningún paciente</p>
          <p class="text-white/30 text-sm">Importa un CSV o añádelos manualmente arriba</p>
        </div>
      @endif

      {{-- Navigation --}}
      <div class="flex justify-between items-center mt-8 pt-6 border-t border-white/10">
        <form action="{{ route('onboarding.previous') }}" method="POST">
          @csrf
          <button type="submit" class="btn bg-white/5 hover:bg-white/10 text-white/60">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
            </svg>
            Anterior
          </button>
        </form>

        <form action="{{ route('onboarding.next') }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-primary">
            Siguiente
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
</x-app-layout>
