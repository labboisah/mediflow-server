@extends('installation.layout')
@section('title', 'Set up MediFlow')
@section('content')
<section class="card">
    <h1>Set up your installation</h1>
    <p>Your licence is active. Review its packages, set your organization branding and {{ $newAdministrator ? 'create the first administrator' : 'confirm the installation settings' }}.</p>
    @include('installation.packages')
</section>
<form method="post" action="{{ route('installation.setup.store') }}" enctype="multipart/form-data">
    @csrf
    <section class="card">
        <h2>Organization and branding</h2>
        <div class="grid">
            <div><label for="brand_name">Organization name</label><input id="brand_name" name="brand_name" required maxlength="120" value="{{ old('brand_name', $settings?->brand_name) }}" /></div>
            <div><label for="address">Address</label><input id="address" name="address" maxlength="255" value="{{ old('address', $settings?->address) }}" /></div>
            <div><label for="logo">Logo (optional)</label><input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" /><p class="muted">PNG, JPEG or WebP, up to 2 MB.</p></div>
            <div><label for="welcome_template">Welcome style</label><select id="welcome_template" name="welcome_template"><option value="auto">Match the primary package</option>@foreach($templates as $key => $template)<option value="{{ $key }}" @selected(old('welcome_template', $settings?->welcome_template) === $key)>{{ $template['label'] }}</option>@endforeach</select></div>
        </div>
        <label for="welcome_heading">Welcome heading (optional)</label><input id="welcome_heading" name="welcome_heading" maxlength="180" value="{{ old('welcome_heading', $settings?->welcome_heading) }}" />
        <label for="welcome_statement">Welcome message (optional)</label><textarea id="welcome_statement" name="welcome_statement" maxlength="1500" rows="3">{{ old('welcome_statement', $settings?->welcome_statement) }}</textarea>
    </section>
    @if($newAdministrator)
        <section class="card">
            <h2>Installation administrator</h2>
            <p>This account will manage branding, setup and staff access. Keep its credentials private.</p>
            <div class="grid">
                <div><label for="admin_name">Administrator name</label><input id="admin_name" name="admin_name" required maxlength="255" value="{{ old('admin_name') }}" /></div>
                <div><label for="admin_email">Email</label><input id="admin_email" type="email" name="admin_email" required maxlength="255" value="{{ old('admin_email') }}" /></div>
                <div><label for="password">Password</label><input id="password" type="password" name="password" required minlength="12" autocomplete="new-password" /></div>
                <div><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" /></div>
            </div>
        </section>
    @else
        <section class="card"><h2>Administrator</h2><p>Signed in as {{ auth()->user()->name }}. Existing accounts and operational records are retained.</p></section>
    @endif
    <div class="actions"><button type="submit">Complete setup and open welcome page</button></div>
</form>
@endsection
