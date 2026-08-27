<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Registro de Equipo' }} · Grupo Hunan</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#fafafa] font-sans text-slate-800 antialiased [background-image:radial-gradient(#d4d4d8_1px,transparent_1px)] [background-size:22px_22px]">
    {{ $slot }}

    @livewireScripts
</body>
</html>
