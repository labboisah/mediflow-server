<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} | Welcome</title>
    <link rel="icon" href="{{ $branding->logoUrl() }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/installation-welcome.css') }}">
</head>
@php
    $appearance = $settings?->welcome_appearance ?? [];
    $colors = $branding->colors($template, $appearance['colors'] ?? []);
    $backgroundImage = $branding->imageUrl('background');
    $heroImage = $branding->imageUrl('hero');
    $supportingImage = $branding->imageUrl('supporting');
@endphp
<body data-custom-colors="{{ !empty(array_filter($appearance['colors'] ?? [])) ? 'true' : 'false' }}" data-template="{{ $template['key'] }}" data-layout="{{ $template['layout'] }}" style="--brand-accent: {{ $colors['accent'] }}; --brand-background: {{ $colors['background'] }}; --brand-text: {{ $colors['text'] }};">
    @php
        $destination = auth()->check() ? route(auth()->user()->is_installation_admin ? 'admin.installation' : 'dashboard') : route('login');
        $workspaceLabel = auth()->check() ? 'Open workspace' : 'Staff login';
        $publicModules = array_intersect(app(\App\Services\LicenseService::class)->enabledModules(), ['patient_records', 'clinical_care', 'maternity', 'laboratory', 'radiology', 'pharmacy', 'billing']);
    @endphp
    <header class="site-header">
        <a class="brand" href="{{ url('/') }}"><img src="{{ $branding->logoUrl() }}" alt="{{ config('app.name') }}"><span>{{ config('app.name') }}<small>{{ $template['label'] }}</small></span></a>
        <a class="button button-outline" href="{{ $destination }}">{{ $workspaceLabel }} <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </header>
    <main>
        <section class="hero {{ $backgroundImage ? 'has-background' : '' }}">
            @if($backgroundImage)<img class="hero-background" src="{{ $backgroundImage }}" alt="" aria-hidden="true">@endif
            <div class="hero-copy">
                <p class="eyebrow"><span></span>{{ $template['eyebrow'] }}</p>
                <h1>{{ $settings?->welcome_heading ?: $template['heading'] }}</h1>
                <p class="welcome-statement">{{ $settings?->welcome_statement ?: $template['statement'] }}</p>
                <div class="actions">
                    <a class="button button-primary" href="{{ $destination }}">{{ $workspaceLabel }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @if(($canManageWifiSharing ?? false) && app(\App\Services\LicenseService::class)->moduleEnabled('maintenance'))
                        <button type="button" class="button button-outline" id="wifiConnectButton" data-connect-url="{{ route('wifi-sharing.connect') }}">Connect others</button>
                        <p id="wifiConnectStatus" role="status">{{ ($wifiSharing['connected'] ?? false) ? 'Wi-Fi access is connected.' : '' }}</p>
                    @endif
                </div>
            </div>
            <aside class="welcome-panel" aria-label="Welcome">
                @if($heroImage)<img class="welcome-artwork" src="{{ $heroImage }}" alt="{{ $appearance['images']['hero']['alt'] ?? '' }}">@else
                <div class="panel-symbol"><i class="bi {{ $template['icon'] }}" aria-hidden="true"></i></div>@endif
                <p class="eyebrow">Welcome to</p>
                <h2>{{ config('app.name') }}</h2>
                @if(config('app.address'))<p class="address">{{ config('app.address') }}</p>@endif
                <div class="panel-note"><i class="bi bi-person-badge" aria-hidden="true"></i><span>A dedicated workspace for our team.</span></div>
            </aside>
        </section>
        @if($supportingImage)<div class="supporting-artwork"><img src="{{ $supportingImage }}" alt="{{ $appearance['images']['supporting']['alt'] ?? '' }}" loading="lazy"></div>@endif
        @if(count($publicModules))
            <section class="services" aria-labelledby="workspace-heading">
                <div><p class="eyebrow">Working together</p><h2 id="workspace-heading">Our workspace</h2></div>
                <div class="service-grid">
                    @foreach($publicModules as $module)
                        <div class="service"><span class="service-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ config('mediflow_modules.features.'.$module) }}</h3><i class="bi bi-check2" aria-hidden="true"></i></div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
    <footer><span>&copy; {{ date('Y') }} {{ config('app.name') }}</span><span>Powered by Mediflow</span></footer>
    <script>
        document.getElementById('wifiConnectButton')?.addEventListener('click', async function () {
            this.disabled = true;
            const status = document.getElementById('wifiConnectStatus');
            status.textContent = 'Connecting...';
            try {
                const response = await fetch(this.dataset.connectUrl, {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}});
                const data = await response.json();
                if (!response.ok || !data.connected) throw new Error('Connection failed');
                status.textContent = 'Connected for Wi-Fi access' + (data.url ? ': ' + data.url : '');
            } catch (error) {
                status.textContent = 'Unable to start Wi-Fi access. Please try again.';
            } finally { this.disabled = false; }
        });
    </script>
</body>
</html>
