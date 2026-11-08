<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Mediflow') }} | {{ $title ?? 'Welcome' }}</title>
    <link rel="icon" href="{{ app(\App\Services\SystemBranding::class)->logoUrl() }}">

    @php
        $viteManifest = public_path('build/manifest.json');
        $hasModernAssets = file_exists($viteManifest)
            && str_contains(file_get_contents($viteManifest), 'resources/css/modern.css')
            && str_contains(file_get_contents($viteManifest), 'resources/js/modern.js');
    @endphp

    @if($hasModernAssets)
        @vite(['resources/css/modern.css', 'resources/js/modern.js'])
    @else
        <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}">
        <link rel="stylesheet" href="{{ asset('css/modern-fallback.css') }}">
        @vite('resources/js/app.js')
    @endif
</head>
<body class="antialiased">
    <main class="min-h-screen bg-med-canvas px-4 py-8">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-sm flex-col justify-center">
            <section class="rounded-md border border-med-line bg-white p-6 shadow-panel">
                {{ $slot }}
            </section>
        </div>
    </main>
</body>
</html>
