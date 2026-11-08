<div class="grid">
    @forelse(($configuration['packages'] ?? []) as $package)
        <article class="package">
            <h3>{{ $package['name'] }}</h3>
            <p>{{ $package['description'] ?? '' }}</p>
            <details open>
                <summary>Included features</summary>
                <ul>
                    @foreach(($package['features'] ?? []) as $feature)
                        <li><strong>{{ $feature['name'] }}</strong>
                            @if($feature['required'] ?? false)<span class="badge">Required</span>@endif
                            <p class="feature-description">{{ $feature['description'] ?? '' }}</p>
                        </li>
                    @endforeach
                </ul>
            </details>
        </article>
    @empty
        <p>No package configuration has been received. Refresh your subscription after purchasing packages.</p>
    @endforelse
</div>
@php
    $packageFeatures = collect($configuration['packages'] ?? [])->flatMap(function ($package) {
        return collect($package['features'] ?? [])->pluck('key')->push('module_'.\Illuminate\Support\Str::snake($package['key'] ?? ''));
    })->all();
    $additionalEntitlements = collect($configuration['entitlements'] ?? [])
        ->filter(fn ($entitlement) => ($entitlement['enabled'] ?? false) && ! in_array($entitlement['key'] ?? '', $packageFeatures, true));
@endphp
@if($additionalEntitlements->isNotEmpty())
    <section class="package">
        <h3>Plan limits</h3>
        <ul>
            @foreach($additionalEntitlements as $entitlement)
                <li><strong>{{ $entitlement['name'] }}</strong>@if($entitlement['limit'] !== null) — {{ $entitlement['limit'] }}@endif</li>
            @endforeach
        </ul>
    </section>
@endif
