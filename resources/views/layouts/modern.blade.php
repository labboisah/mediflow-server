<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $modernPageTitle = $pageTitle ?? trim($__env->yieldContent('page-title')) ?: 'Workspace';
        $modernPageSubtitle = $pageSubtitle ?? trim($__env->yieldContent('page-subtitle')) ?: config('app.name');
        $modernBrowserTitle = trim($__env->yieldContent('title')) ?: $modernPageTitle;
        $viteManifest = public_path('build/manifest.json');
        $hasModernAssets = file_exists($viteManifest)
            && str_contains(file_get_contents($viteManifest), 'resources/css/modern.css')
            && str_contains(file_get_contents($viteManifest), 'resources/js/modern.js');
    @endphp

    <title>{{ config('app.name') }} | {{ $modernBrowserTitle }}</title>

    <link rel="icon" href="{{ app(\App\Services\SystemBranding::class)->logoUrl() }}">

    @if($hasModernAssets)
        @vite(['resources/css/modern.css', 'resources/js/modern.js'])
    @else
        <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}">
        <link rel="stylesheet" href="{{ asset('css/modern-fallback.css') }}">
        <link rel="stylesheet" href="{{ asset('css/modern.css') }}">
        @vite('resources/js/app.js')
    @endif
    @livewireStyles
    @stack('styles')
</head>
<body class="antialiased">
    <div x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('modern-sidebar-collapsed') === 'true',
            toggleSidebarCollapsed() {
                this.sidebarCollapsed = ! this.sidebarCollapsed;
                localStorage.setItem('modern-sidebar-collapsed', this.sidebarCollapsed ? 'true' : 'false');
            },
            expandSidebar() {
                this.sidebarCollapsed = false;
                localStorage.setItem('modern-sidebar-collapsed', 'false');
            }
        }"
        class="mf-shell">
        @auth
            <div x-show="sidebarOpen"
                 x-transition.opacity
                 x-cloak
                 class="fixed inset-0 z-30 bg-med-ink/35 lg:hidden"
                 @click="sidebarOpen = false"></div>

            @include('layouts.partials.modern-sidebar')

            <div class="min-h-screen transition-all duration-200"
                 :class="{ 'lg:pl-24': sidebarCollapsed, 'lg:pl-80': ! sidebarCollapsed }">
                @include('layouts.partials.modern-topbar')

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-screen-2xl">
                        @include('layouts.partials.modern-alerts')

                        @hasSection('content')
                            @yield('content')
                        @else
                            {{ $slot ?? '' }}
                        @endif
                    </div>
                </main>
            </div>
        @else
            <main class="flex min-h-screen items-center justify-center px-4 py-10">
                <div class="w-full max-w-md">
                    @include('layouts.partials.modern-alerts')

                    @hasSection('content')
                        @yield('content')
                    @else
                        {{ $slot ?? '' }}
                    @endif
                </div>
            </main>
        @endauth

        <div x-data="{ show: false, message: '', type: 'success' }"
             x-on:mediflow-toast.window="
                message = $event.detail.message;
                type = $event.detail.type || 'success';
                show = true;
                setTimeout(() => show = false, 3500);
             "
             x-show="show"
             x-transition
             x-cloak
             class="fixed right-4 top-4 z-50 max-w-sm rounded-md border bg-white px-4 py-3 text-sm shadow-panel"
             :class="{
                'border-med-primary text-med-primary': type === 'success',
                'border-med-danger text-med-danger': type === 'danger',
                'border-med-accent text-med-ink': type === 'warning'
             }">
            <span x-text="message"></span>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
    @stack('modals')
    @stack('vite')
</body>
</html>
