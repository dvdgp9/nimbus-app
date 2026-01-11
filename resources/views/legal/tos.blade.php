@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6">Términos de Servicio</h1>
        <p class="mb-4 text-gray-600">Última actualización: {{ date('d/m/Y') }}</p>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">1. Aceptación de los términos</h2>
            <p>Al acceder y utilizar Nimbus, aceptas cumplir con estos Términos de Servicio y todas las leyes y regulaciones aplicables.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">2. Uso del Servicio</h2>
            <p>Nimbus es una herramienta de gestión de citas y recordatorios que se integra con Google Calendar. Te comprometes a usar el servicio de manera responsable y legal.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">3. Integración con Google</h2>
            <p>El uso de Nimbus requiere una cuenta de Google. Eres responsable de mantener la seguridad de tu cuenta y de los permisos que otorgas a la aplicación a través de OAuth.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">4. Limitación de Responsabilidad</h2>
            <p>Nimbus se proporciona "tal cual" sin garantías de ningún tipo. No nos hacemos responsables de pérdidas de datos o interrupciones del servicio derivadas del uso de las APIs de Google.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">5. Modificaciones</h2>
            <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. El uso continuado de la aplicación tras dichos cambios constituye la aceptación de los nuevos términos.</p>
        </section>

        <div class="mt-8">
            <a href="{{ url('/') }}" class="text-blue-600 hover:underline">Volver al inicio</a>
        </div>
    </div>
</div>
@endsection
