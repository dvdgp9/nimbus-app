@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6">Política de Privacidad</h1>
        <p class="mb-4 text-gray-600">Última actualización: {{ date('d/m/Y') }}</p>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">1. Información que recopilamos</h2>
            <p>Nimbus recopila información para proporcionar mejores servicios a sus usuarios. Recopilamos:</p>
            <ul class="list-disc ml-6 mt-2">
                <li><strong>Información de la cuenta de Google:</strong> Correo electrónico y nombre de perfil para identificarte en la aplicación.</li>
                <li><strong>Datos de Google Calendar:</strong> Accedemos a tus calendarios y eventos para permitirte gestionarlos desde Nimbus y enviarte recordatorios.</li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">2. Cómo usamos la información</h2>
            <p>Utilizamos la información recopilada para:</p>
            <ul class="list-disc ml-6 mt-2">
                <li>Proporcionar, mantener y mejorar nuestros servicios.</li>
                <li>Sincronizar tus eventos de Google Calendar con la plataforma Nimbus.</li>
                <li>Enviar notificaciones y recordatorios de tus citas.</li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">3. Uso de Scopes de Google API</h2>
            <p>Nimbus utiliza el permiso <code>https://www.googleapis.com/auth/calendar</code> específicamente para leer tus calendarios y eventos con el fin de automatizar recordatorios. No compartimos estos datos con terceros ni los utilizamos para fines publicitarios.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">4. Seguridad de los datos</h2>
            <p>Tus tokens de acceso a Google se almacenan de forma segura y encriptada en nuestra base de datos. Puedes revocar el acceso en cualquier momento desde la configuración de tu cuenta de Google.</p>
        </section>

        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-3">5. Contacto</h2>
            <p>Si tienes preguntas sobre esta Política de Privacidad, puedes contactarnos a través del correo de soporte configurado en la aplicación.</p>
        </section>

        <div class="mt-8">
            <a href="{{ url('/') }}" class="text-blue-600 hover:underline">Volver al inicio</a>
        </div>
    </div>
</div>
@endsection
