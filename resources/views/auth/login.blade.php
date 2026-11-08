<x-guest-layout>
    <x-slot name="title">Login</x-slot>

    <div class="mb-5 text-center">
        <a href="{{ url('/') }}" class="mx-auto mb-4 inline-flex items-center justify-center">
            <img src="{{ app(\App\Services\SystemBranding::class)->logoUrl() }}" alt="{{ config('app.name') }}" class="h-12 w-12 rounded-md object-contain" width="48" height="48" style="width:48px;height:48px;max-width:48px;object-fit:contain;">
        </a>
        <h1 class="text-xl font-semibold text-med-ink">Login to {{ config('app.name') }}</h1>
        <p class="mt-1 text-sm text-med-muted">Access your healthcare workspace.</p>
    </div>

    <x-auth-session-status class="mb-4 rounded-md border border-med-line bg-med-canvas px-4 py-3 text-sm text-med-muted" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <label class="block">
            <span class="mb-1 block text-sm font-medium text-med-ink">Email Address</span>
            <input id="email" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="your@email.com">
            @error('email')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
        </label>

        <label class="block">
            <span class="mb-1 block text-sm font-medium text-med-ink">Password</span>
            <input id="password" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
            @error('password')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
        </label>

        <button type="submit" class="mf-focus inline-flex w-full items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-med-primaryDark">
            Login
        </button>

        <div class="flex items-center justify-between gap-3 text-sm">
            <a href="{{ url('/') }}" class="font-semibold text-med-primary hover:text-med-primaryDark">Back to home</a>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-med-muted hover:text-med-primary">Forgot password?</a>
            @endif
        </div>
    </form>
</x-guest-layout>

