<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Suspendida</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-lg shadow-xl p-8 text-center">
                <!-- Icono -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <!-- Título -->
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    Cuenta Suspendida
                </h1>

                <!-- Nombre del tenant -->
                <p class="text-lg text-gray-600 mb-4">
                    {{ $tenant->name }}
                </p>

                <!-- Razón -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <p class="text-sm font-semibold text-red-800 mb-1">
                        Razón de la suspensión:
                    </p>
                    <p class="text-sm text-red-700">
                        {{ $reason }}
                    </p>
                </div>

                <!-- Mensaje -->
                <p class="text-gray-600 mb-6">
                    Su cuenta ha sido suspendida temporalmente. Para más información o para resolver esta situación, por favor contacte con soporte.
                </p>

                <!-- Botones -->
                <div class="space-y-3">
                    <a href="mailto:juanhurtado23@gmail.com" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition">
                        Contactar Soporte
                    </a>
                    <a href="{{ config('/') }}" class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-3 px-4 rounded-lg transition">
                        Volver al Inicio
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-sm text-gray-500 mt-6">
                © {{ date('Y') }} {{ config('app.name') }}
            </p>
        </div>
    </div>
</body>
</html>