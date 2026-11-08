<div>
    <x-ui.page title="Installation Setup" subtitle="Configure your organization's branding, welcome page, package, and available features.">
        @if(config('central_licensing.enabled'))
            <div class="mb-4 rounded-md border border-med-primary bg-white p-4">
                Packages and licence validity are managed in KernelBridge.
                <a href="{{ route('kernelbridge.license.show') }}" class="font-semibold underline">Activate or verify this computer</a>
            </div>
        @endif
        @if($savedMessage)
            <div role="status" class="rounded-md border border-med-primary bg-white p-4 text-med-primary">{{ $savedMessage }}</div>
        @endif
        <form wire:submit="saveBranding" class="space-y-6">
            <x-ui.card title="System branding" subtitle="Branding changes are saved separately from package access.">
                @if($brandingMessage)<p role="status" class="mb-4 text-sm text-med-primary">{{ $brandingMessage }}</p>@endif
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Brand name" name="brandName" wire:model.live.debounce.400ms="brandName" maxlength="120" required />
                    <x-ui.input label="Organization address" name="brandAddress" wire:model="brandAddress" maxlength="255" />
                    <div>
                        <x-ui.input type="file" label="Logo" name="logo" wire:model="logo" accept="image/png,image/jpeg,image/webp" />
                        <p class="mt-2 text-xs text-med-muted">PNG, JPG or WebP. Up to 2 MB and 2048 x 2048 pixels.</p>
                        <p wire:loading wire:target="logo" class="text-sm text-med-muted">Uploading logo...</p>
                        <label class="mt-3 flex items-center gap-2 text-sm text-med-muted"><input type="checkbox" wire:model.live="removeLogo">Use default logo</label>
                    </div>
                    <x-ui.select label="Welcome page template" name="welcomeTemplate" wire:model.live="welcomeTemplate">
                        <option value="auto">Automatic - match installation package</option>
                        @foreach($welcomeTemplates as $key => $option)<option value="{{ $key }}">{{ $option['label'] }}</option>@endforeach
                    </x-ui.select>
                    <x-ui.input label="Welcome heading (optional)" name="welcomeHeading" wire:model.live.debounce.400ms="welcomeHeading" maxlength="180" placeholder="Use the template heading" />
                    <x-ui.textarea label="Welcome statement (optional)" name="welcomeStatement" wire:model.live.debounce.400ms="welcomeStatement" rows="3" maxlength="1500" placeholder="Use the template welcome statement" />
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach(['accent' => 'Accent color', 'background' => 'Welcome background color', 'text' => 'Welcome text color'] as $key => $label)
                        <div>
                            <x-ui.input label="{{ $label }}" name="welcomeColors.{{ $key }}" wire:model.live.debounce.400ms="welcomeColors.{{ $key }}" placeholder="Template default (#RRGGBB)" maxlength="7" />
                            <input type="color" aria-label="Choose {{ strtolower($label) }}" value="{{ $previewColors[$key] }}" wire:change="$set('welcomeColors.{{ $key }}', $event.target.value)">
                            <button type="button" class="text-sm underline" wire:click="$set('welcomeColors.{{ $key }}', '')">Use template default</button>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-med-muted">These colors and images apply to the public welcome page. Use colors that keep the text readable. Background images have a light overlay.</p>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach($artworkSlots as $slot => $label)
                        <div wire:key="artwork-{{ $slot }}">
                            <x-ui.input type="file" label="{{ $label }}" name="artwork.{{ $slot }}" wire:model="artwork.{{ $slot }}" accept="image/png,image/jpeg,image/webp" />
                            <p class="text-xs text-med-muted">PNG, JPG or WebP; up to 4 MB, 4096 x 4096 pixels.</p>
                            <p wire:loading wire:target="artwork.{{ $slot }}" class="text-sm">Uploading image...</p>
                            <label class="my-3 flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="removeArtwork.{{ $slot }}">Remove image</label>
                            @if($slot !== 'background')
                                <x-ui.input label="Image description (optional)" name="artworkAlt.{{ $slot }}" wire:model="artworkAlt.{{ $slot }}" maxlength="180" placeholder="Describe the image for screen readers" />
                            @endif
                            @php
                                $artworkPreview = empty($removeArtwork[$slot]) ? ((!empty($artwork[$slot]) && !$errors->has('artwork.'.$slot)) ? $artwork[$slot]->temporaryUrl() : app(\App\Services\SystemBranding::class)->imageUrl($slot)) : null;
                            @endphp
                            @if($artworkPreview)<img src="{{ $artworkPreview }}" alt="{{ $label }} preview" class="mt-3" style="width:100%;height:150px;object-fit:contain">@endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 rounded-md border border-med-line p-6" style="background: {{ $previewColors['background'] }}; color: {{ $previewColors['text'] }}">
                    <p class="mb-3 text-xs font-semibold uppercase">Welcome preview - {{ $welcomePreview['label'] }}</p>
                    <img src="{{ $removeLogo ? asset('images/logo.png') : (($logo && !$errors->has('logo')) ? $logo->temporaryUrl() : $currentLogoUrl) }}" alt="Logo preview" style="width:64px;height:64px;object-fit:contain" class="mb-4">
                    <p class="text-sm font-semibold">{{ $brandName }}</p>
                    <h2 class="mt-2 text-2xl font-semibold">{{ $welcomeHeading ?: $welcomePreview['heading'] }}</h2>
                    <p class="mt-3 max-w-2xl whitespace-pre-line text-sm leading-6">{{ $welcomeStatement ?: $welcomePreview['statement'] }}</p>
                    <span class="mt-4 inline-block rounded-md px-4 py-2 text-sm" style="background: {{ $previewColors['accent'] }}; color: #fff">Staff login</span>
                </div>
                <div class="mt-5 flex flex-wrap items-center gap-4">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveBranding,logo,artwork">Save system branding</x-ui.button>
                    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="text-sm font-semibold text-med-primary">View saved welcome page <i class="bi bi-box-arrow-up-right"></i></a>
                </div>
            </x-ui.card>
        </form>
        <form wire:submit="saveAdministrator" class="space-y-4">
            <x-ui.card title="Administrator registration" subtitle="Choose the administrator's login details. Saving branding or package settings will not change this account.">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Full name" name="adminName" wire:model="adminName" required maxlength="255" autocomplete="name" />
                    <x-ui.input label="Email address" name="adminEmail" type="email" wire:model="adminEmail" required maxlength="255" autocomplete="username" />
                    <x-ui.input label="{{ $administratorEmail ? 'New password (optional)' : 'Password' }}" name="adminPassword" type="password" wire:model="adminPassword" autocomplete="new-password" />
                    <x-ui.input label="Confirm password" name="adminPassword_confirmation" type="password" wire:model="adminPassword_confirmation" autocomplete="new-password" />
                </div>
                <p class="mt-3 text-sm text-med-muted">Use at least 8 characters. When updating an existing administrator, leave both password fields blank to keep the current password. Save system branding first when setting up a new installation.</p>
                @if($administratorEmail)<p class="mt-2 text-sm">Current login: <strong>{{ $administratorEmail }}</strong></p>@endif
                @if($administratorMessage)<p role="status" class="mt-3 text-sm text-med-primary">{{ $administratorMessage }}</p>@endif
                <p class="mt-2 text-sm text-med-muted">This creates an operational administrator. Installation Setup remains restricted to the super administrator.</p>
                <x-ui.button type="submit" class="mt-4" wire:loading.attr="disabled" wire:target="saveAdministrator">{{ $administratorEmail ? 'Update administrator' : 'Register administrator' }}</x-ui.button>
            </x-ui.card>
        </form>
        @if(config('central_licensing.enabled'))
            <x-ui.card title="Installed packages and features" subtitle="Change packages and settle invoices in KernelBridge, then refresh the subscription here.">
                @if(session('kernelbridge_license_status'))<p role="status" class="mb-3">{{ session('kernelbridge_license_status') }}</p>@endif
                @if($errors->has('license_key'))<p role="alert" class="mb-3 text-med-danger">{{ $errors->first('license_key') }}</p>@endif
                <form method="post" action="{{ route('kernelbridge.license.verify') }}" class="mb-4">@csrf<button type="submit" class="rounded-md bg-med-primary px-4 py-2 text-white">Refresh subscription</button></form>
                <dl class="mb-4 grid gap-2 rounded-md border border-med-line p-4 text-sm md:grid-cols-2">
                    <div><dt class="font-semibold">Entitlement source</dt><dd>{{ $entitlementDiagnostics['source'] ?? 'unavailable' }}</dd></div>
                    <div><dt class="font-semibold">License status</dt><dd>{{ $entitlementDiagnostics['license_status'] ?? 'unknown' }}{{ empty($entitlementDiagnostics['license_usable']) ? ' ('.$entitlementDiagnostics['license_reason'].')' : '' }}</dd></div>
                    <div><dt class="font-semibold">Catalog revision</dt><dd>{{ $entitlementDiagnostics['catalog_revision'] ?? 'not available' }}</dd></div>
                    <div><dt class="font-semibold">Source revision</dt><dd>{{ $entitlementDiagnostics['source_revision'] ?? 'not available' }}</dd></div>
                    <div><dt class="font-semibold">Entitlement revision</dt><dd>{{ $entitlementDiagnostics['entitlement_revision'] ?? 'not available' }}</dd></div>
                    <div><dt class="font-semibold">Snapshot age</dt><dd>{{ isset($entitlementDiagnostics['snapshot_age_seconds']) ? $entitlementDiagnostics['snapshot_age_seconds'].' seconds' : 'not available' }}</dd></div>
                    <div><dt class="font-semibold">Last successful refresh</dt><dd>{{ $entitlementDiagnostics['last_successful_verification_at'] ?? 'not available' }}</dd></div>
                    <div><dt class="font-semibold">Offline access expires</dt><dd>{{ $entitlementDiagnostics['offline_grace_expires_at'] ?? 'not available' }}</dd></div>
                    @if($entitlementDiagnostics['last_error_code'] ?? null)
                        <div><dt class="font-semibold">Last verification error</dt><dd>{{ $entitlementDiagnostics['last_error_code'] }}</dd></div>
                    @endif
                </dl>
                <h3 class="mb-3 font-semibold">Modules granted to this installation</h3>
                @if($grantedModules)
                    <ul class="mb-4 grid gap-2 text-sm md:grid-cols-2">
                        @foreach($grantedModules as $module)
                            <li class="rounded border border-med-line px-3 py-2">{{ $features[$module] ?? $module }}</li>
                        @endforeach
                    </ul>
                @else
                    <p role="status" class="mb-4 text-sm text-med-danger">No MediFlow modules are currently granted in the active entitlement snapshot. Refresh the subscription or contact the KernelBridge administrator; local package selection is disabled.</p>
                @endif
                <p class="text-sm text-med-muted">Package and module access is read from the active KernelBridge entitlement. To change it, update the plan in KernelBridge and refresh the subscription here. This screen cannot assign packages or override plan grants.</p>
            </x-ui.card>
        @else
        <form wire:submit="save" class="space-y-6">
            <x-ui.card title="Organization and package" subtitle="Selecting a package loads its recommended modules. Customize the selection below before saving.">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Organization name" name="clientName" wire:model="clientName" required />
                    <x-ui.select label="Installation package" name="plan" wire:model.live="plan">
                        @foreach($plans as $key => $package)
                            <option value="{{ $key }}">{{ $package['label'] }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.input type="date" label="Access expiry (optional)" name="expiresAt" wire:model="expiresAt" />
                    <label class="flex items-center gap-3 text-sm text-med-ink">
                        <input type="checkbox" wire:model="active" class="rounded border-med-line text-med-primary">
                        Installation active
                    </label>
                </div>
                <p class="mt-4 text-sm text-med-muted">{{ $plans[$plan]['description'] ?? '' }}</p>
            </x-ui.card>
            <x-ui.card title="Companion packages" subtitle="Primary package changes reset the preset. Companion selections add suggested modules; review and remove unwanted module selections before saving.">
                <div class="grid gap-3 md:grid-cols-3">@foreach($plans as $key => $package)
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="selectedPackages" value="{{ $key }}" @disabled($plan === $key)>{{ $package['label'] }}</label>
                @endforeach</div>
                @error('selectedPackages')<p class="text-med-danger">{{ $message }}</p>@enderror
            </x-ui.card>
            <x-ui.card title="Available modules" subtitle="Core System and Access Control remain available. Staff roles and module assignments still control what each user can do.">
                <p class="mb-4 text-sm font-semibold text-med-primary">{{ count(array_unique(array_merge($required, $modules))) }} modules selected</p>
                @error('modules')<p role="alert" class="mb-4 text-sm text-med-danger">{{ $message }}</p>@enderror
                @error('modules.*')<p role="alert" class="mb-4 text-sm text-med-danger">{{ $message }}</p>@enderror
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($features as $key => $label)
                        <label wire:key="module-{{ $key }}" class="flex items-start gap-3 rounded-md border border-med-line bg-white p-4">
                            @if(in_array($key, $required, true))
                                <input type="checkbox" checked disabled class="mt-1 rounded border-med-line text-med-primary">
                            @else
                                <input type="checkbox" value="{{ $key }}" wire:model.live="modules" class="mt-1 rounded border-med-line text-med-primary">
                            @endif
                            <span>
                                <span class="block text-sm font-semibold text-med-ink">{{ $label }}</span>
                                @if(in_array($key, $required, true))
                                    <span class="text-xs text-med-muted">Required for every installation</span>
                                @elseif($key === 'branch_management')
                                    <span class="text-xs text-med-muted">Enterprise package only</span>
                                @elseif(!empty($dependencies[$key]))
                                    <span class="text-xs text-med-muted">Requires {{ implode(', ', array_map(fn ($dependency) => $features[$dependency], $dependencies[$key])) }}</span>
                                @else
                                    <span class="text-xs text-med-muted">Optional module</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </x-ui.card>

            <div class="flex flex-wrap items-center justify-between gap-4 rounded-md border border-med-line bg-white p-4">
                <p class="text-sm text-med-muted">Changes apply immediately. Disabling a module preserves its existing records.</p>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save installation</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </x-ui.button>
            </div>
        </form>
        @endif
    </x-ui.page>
</div>
