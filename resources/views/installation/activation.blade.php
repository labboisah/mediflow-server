@extends('installation.layout')
@section('title', 'Activate MediFlow')
@section('content')
<section class="card">
    <h1>Activate this MediFlow installation</h1>
    <p>Enter the one-time activation code from your KernelBridge licensing account. Your packages and entitlements will be downloaded for this installation.</p>
    <p class="muted">The activation code is exchanged for an installation-bound credential and is not retained. Keep this browser open until activation completes.</p>
    @if($needsLogin)
        <p>An installation administrator already exists. Sign in to reactivate this computer.</p>
        <a class="button" href="{{ route('login') }}">Administrator sign in</a>
    @else
        <form method="post" action="{{ route('kernelbridge.license.activate') }}">
            @csrf
            <label for="server_url">KernelBridge server URL</label>
            <input id="server_url" name="server_url" type="url" required maxlength="2048" value="{{ old('server_url', config('kernelbridge-licensing.api_url')) }}" placeholder="http://kernelbridge.test" />
            <p class="muted">Enter your authorized licensing server address. The /api/v1 path is added automatically. A different server must be authorized by your deployment administrator before the saved credential can be used there.</p>
            <label for="activation_code">Activation code</label>
            <input id="activation_code" name="activation_code" required maxlength="255" autocomplete="one-time-code" spellcheck="false" placeholder="KBI-..." />
            <label for="device_name">Computer name</label>
            <input id="device_name" name="device_name" maxlength="255" value="MediFlow installation" />
            <div class="actions"><button type="submit">Activate and continue to setup</button></div>
        </form>
    @endif
</section>
@endsection
