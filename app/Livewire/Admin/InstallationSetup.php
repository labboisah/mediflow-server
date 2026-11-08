<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\ClientLicense;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CentralLicenseService;
use App\Services\InstallationAdministrator;
use App\Services\LicenseService;
use App\Services\SystemBranding;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use KernelBridge\LicensingClient\Services\LicenseCacheService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.modern')]
class InstallationSetup extends Component
{
    use WithFileUploads;

    public string $brandName = '';

    public string $brandAddress = '';

    public string $welcomeHeading = '';

    public string $welcomeStatement = '';

    public string $welcomeTemplate = 'auto';

    public array $artwork = [];

    public array $removeArtwork = [];

    public array $artworkAlt = [];

    public array $welcomeColors = ['accent' => '', 'background' => '', 'text' => ''];

    public $logo;

    public bool $removeLogo = false;

    public string $brandingMessage = '';

    public string $administratorMessage = '';

    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPassword = '';

    public string $adminPassword_confirmation = '';

    public string $clientName = '';

    public string $plan = 'hospital';

    public array $modules = [];

    public array $selectedPackages = [];

    public string $expiresAt = '';

    public bool $active = true;

    public string $savedMessage = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->is_installation_admin, 403);
    }

    public function mount(LicenseService $license): void
    {
        $settings = app(SystemBranding::class)->settings();
        $this->brandName = $settings?->brand_name ?? config('app.name');
        $administrator = User::find($settings?->administrator_user_id);
        $this->adminName = $administrator?->name ?? $this->brandName.' Administrator';
        try {
            $suggestedEmail = app(InstallationAdministrator::class)->email($this->brandName);
        } catch (ValidationException $e) {
            $suggestedEmail = '';
        }
        $this->adminEmail = $administrator?->email ?? $suggestedEmail;
        $this->brandAddress = $settings?->address ?? config('app.address', '');
        $this->welcomeHeading = $settings?->welcome_heading ?? '';
        $this->welcomeStatement = $settings?->welcome_statement ?? '';
        $this->welcomeTemplate = $settings?->welcome_template ?? 'auto';

        $appearance = $settings?->welcome_appearance ?? [];
        foreach (SystemBranding::ARTWORK as $slot => $label) {
            $this->artworkAlt[$slot] = $appearance['images'][$slot]['alt'] ?? '';
        }
        foreach (array_keys($this->welcomeColors) as $key) {
            $this->welcomeColors[$key] = $appearance['colors'][$key] ?? '';
        }

        $current = $license->currentLicense();
        $this->clientName = $current?->client_name ?? config('app.title', config('app.name'));
        $this->plan = $license->currentPlan() ?? 'hospital';
        $this->modules = $license->configuredModules();
        $this->selectedPackages = $license->selectedPackages();
        $this->expiresAt = $current?->expires_at?->format('Y-m-d') ?? '';
        $this->active = $current?->is_active ?? true;
    }

    public function updatedPlan(): void
    {
        abort_if(config('central_licensing.enabled'), 403, 'The installation plan is managed by KernelBridge.');
        $this->validateOnly('plan', ['plan' => ['required', Rule::in(array_keys(config('mediflow_modules.plans')))]]);
        $this->selectedPackages = [$this->plan];
        $this->modules = app(LicenseService::class)->planModules($this->plan);
        $this->savedMessage = '';
    }

    public function updatedSelectedPackages(): void
    {
        abort_if(config('central_licensing.enabled'), 403, 'Installation packages are managed by KernelBridge.');
        $this->validateOnly('selectedPackages', [
            'selectedPackages' => ['required', 'array', 'min:1'],
            'selectedPackages.*' => ['string', 'distinct', Rule::in(array_keys(config('mediflow_modules.plans')))],
        ]);
        $this->selectedPackages = array_values(array_unique(array_merge([$this->plan], $this->selectedPackages)));
        $this->modules = array_values(array_unique(array_merge($this->modules,
            app(LicenseService::class)->packageModules($this->selectedPackages))));
    }

    public function save(): void
    {
        if (config('central_licensing.enabled')) {
            $this->addError('modules', 'Packages and validity are managed in KernelBridge. Open /license to activate or verify this computer.');

            return;
        }
        abort_unless(auth()->user()?->is_installation_admin, 403);
        $this->validate([
            'clientName' => ['required', 'string', 'max:255'],
            'plan' => ['required', Rule::in(array_keys(config('mediflow_modules.plans')))],
            'selectedPackages' => ['required', 'array', 'min:1'],
            'selectedPackages.*' => ['string', 'distinct', Rule::in(array_keys(config('mediflow_modules.plans')))],
            'modules' => ['required', 'array'],
            'modules.*' => ['string', 'distinct', Rule::in(array_keys(config('mediflow_modules.features')))],
            'expiresAt' => ['nullable', 'date_format:Y-m-d'],
            'active' => ['boolean'],
        ]);
        $licenseService = app(LicenseService::class);
        $required = config('mediflow_modules.required', []);
        $selected = array_values(array_unique(array_merge($required, $this->modules)));
        foreach ($selected as $module) {
            foreach (config("mediflow_modules.dependencies.{$module}", []) as $dependency) {
                if (! in_array($dependency, $selected, true)) {
                    $this->addError('modules', config("mediflow_modules.features.{$module}").' requires '.config("mediflow_modules.features.{$dependency}").'.');

                    return;
                }
            }
        }
        if (! in_array($this->plan, $this->selectedPackages, true)) {
            $this->addError('selectedPackages', 'The primary package must be selected.');

            return;
        }
        if (! in_array('enterprise_hospital', $this->selectedPackages, true) && in_array('branch_management', $selected, true)) {
            $this->addError('modules', 'Branch Management requires the Enterprise Hospital package.');

            return;
        }

        DB::transaction(function () use ($selected, $licenseService) {
            $license = ClientLicense::query()->latest('id')->lockForUpdate()->first() ?? new ClientLicense;
            $before = ['plan' => $license->plan, 'modules' => $licenseService->configuredModules(), 'is_active' => $license->is_active];
            $license->fill([
                'client_name' => $this->clientName,
                'plan' => $this->plan,
                'selected_packages' => $this->selectedPackages,
                'expires_at' => $this->expiresAt ? Carbon::parse($this->expiresAt)->endOfDay() : null,
                'starts_at' => $license->starts_at ?? now(),
                'is_active' => $this->active,
            ])->save();
            $license->enabledModules()->delete();
            foreach (array_keys(config('mediflow_modules.features')) as $module) {
                $license->enabledModules()->create(['module_name' => $module, 'is_enabled' => in_array($module, $selected, true)]);
            }
            AuditLog::create([
                'actor_id' => auth()->id(), 'action' => 'installation.configured',
                'model_type' => ClientLicense::class, 'model_id' => $license->id,
                'before' => $before,
                'after' => ['selected_packages' => $this->selectedPackages, 'client_name' => $this->clientName, 'plan' => $this->plan, 'modules' => $selected, 'is_active' => $this->active, 'expires_at' => $this->expiresAt],
                'ip' => request()->ip(), 'user_agent' => request()->userAgent(),
            ]);
        });
        $this->modules = $selected;
        $this->savedMessage = 'Installation saved. Package restrictions now apply to all users. Existing records are retained.';
    }

    private function brandingRules(): array
    {
        return [
            'brandName' => ['required', 'string', 'max:120'],
            'brandAddress' => ['nullable', 'string', 'max:255'],
            'welcomeHeading' => ['nullable', 'string', 'max:180'],
            'welcomeStatement' => ['nullable', 'string', 'max:1500'],
            'welcomeTemplate' => ['required', Rule::in(array_merge(['auto'], array_keys(config('welcome_templates'))))],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=2048,max_height=2048'],
            'removeLogo' => ['boolean'],
            'artwork' => ['array:background,hero,supporting'],
            'artwork.*' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096', 'dimensions:max_width=4096,max_height=4096'],
            'removeArtwork' => ['array:background,hero,supporting'],
            'removeArtwork.*' => ['boolean'],
            'artworkAlt' => ['array:background,hero,supporting'],
            'artworkAlt.*' => ['nullable', 'string', 'max:180'],
            'welcomeColors' => ['array:accent,background,text'],
            'welcomeColors.*' => ['nullable', 'regex:/^#[a-fA-F0-9]{6}$/D'],
        ];
    }

    public function updatedLogo(): void
    {
        $this->validateOnly('logo', $this->brandingRules());
        $this->removeLogo = false;
    }

    public function updatedArtwork($value, $key): void
    {
        $this->validateOnly('artwork.'.$key, $this->brandingRules());
        $this->removeArtwork[$key] = false;
    }

    public function saveBranding(): void
    {
        abort_unless(auth()->user()?->is_installation_admin, 403);
        $this->validate($this->brandingRules());
        $newArtwork = [];
        $obsoleteArtwork = [];
        $newPath = null;
        $oldPath = null;
        try {
            if ($this->logo && ! $this->removeLogo) {
                $newPath = $this->logo->store('branding', 'local');
                if (! $newPath) {
                    $this->addError('logo', 'The logo could not be stored. Please try again.');

                    return;
                }
            }
            foreach (SystemBranding::ARTWORK as $slot => $label) {
                if (! empty($this->artwork[$slot]) && empty($this->removeArtwork[$slot])) {
                    $newArtwork[$slot] = $this->artwork[$slot]->store('branding', 'local');
                    if (! $newArtwork[$slot]) {
                        throw ValidationException::withMessages(['artwork.'.$slot => 'The image could not be stored. Please try again.']);
                    }
                }
            }
            DB::transaction(function () use ($newPath, &$oldPath, $newArtwork, &$obsoleteArtwork) {
                $settings = SystemSetting::query()->lockForUpdate()->find(1) ?? new SystemSetting;
                $before = $settings->only(['brand_name', 'address', 'welcome_heading', 'welcome_statement', 'welcome_template', 'logo_path', 'welcome_appearance']);
                $oldPath = $settings->logo_path;
                $appearance = $settings->welcome_appearance ?? [];
                $appearance['colors'] = $this->welcomeColors;
                foreach (SystemBranding::ARTWORK as $slot => $label) {
                    $previous = $appearance['images'][$slot]['path'] ?? null;
                    $path = $newArtwork[$slot] ?? (! empty($this->removeArtwork[$slot]) ? null : $previous);
                    if ($previous && $previous !== $path && str_starts_with($previous, 'branding/')) {
                        $obsoleteArtwork[] = $previous;
                    }
                    $appearance['images'][$slot] = ['path' => $path, 'alt' => trim($this->artworkAlt[$slot] ?? '')];
                }
                $settings->welcome_appearance = $appearance;
                $settings->id = 1;
                $settings->fill([
                    'brand_name' => trim($this->brandName),
                    'address' => trim($this->brandAddress),
                    'welcome_heading' => trim($this->welcomeHeading) ?: null,
                    'welcome_statement' => trim($this->welcomeStatement) ?: null,
                    'welcome_template' => $this->welcomeTemplate,
                    'logo_path' => $newPath ?: ($this->removeLogo ? null : $oldPath),
                ])->save();
                AuditLog::create([
                    'actor_id' => auth()->id(), 'action' => 'system.branding_updated',
                    'model_type' => SystemSetting::class, 'model_id' => 1,
                    'before' => $before, 'after' => $settings->only(array_keys($before)),
                    'ip' => request()->ip(), 'user_agent' => request()->userAgent(),
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values(array_filter($newArtwork)));
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }
        if ($oldPath && ($newPath || $this->removeLogo) && str_starts_with($oldPath, 'branding/')) {
            Storage::disk('local')->delete($oldPath);
        }
        Storage::disk('local')->delete($obsoleteArtwork);
        $this->reset('logo', 'removeLogo', 'artwork', 'removeArtwork');
        app(SystemBranding::class)->apply();
        $this->brandingMessage = 'System branding saved. The welcome page, login, and application branding are updated.';
    }

    public function saveAdministrator(): void
    {
        abort_unless(auth()->user()?->is_installation_admin, 403);
        $this->administratorMessage = '';
        try {
            $result = app(InstallationAdministrator::class)->save([
                'adminName' => $this->adminName, 'adminEmail' => $this->adminEmail,
                'adminPassword' => $this->adminPassword, 'adminPassword_confirmation' => $this->adminPassword_confirmation,
            ]);
            $this->adminName = $result['name'];
            $this->adminEmail = $result['email'];
            $this->administratorMessage = ($result['created'] ? 'Administrator registered.' : 'Administrator updated.').' Login: '.$result['email'];
        } finally {
            $this->reset('adminPassword', 'adminPassword_confirmation');
        }
    }

    public function render()
    {
        return view('components.admin.installation-setup', [
            'entitlementDiagnostics' => config('central_licensing.enabled') ? app(CentralLicenseService::class)->entitlementDiagnostics() : [],
            'administratorEmail' => User::find(app(SystemBranding::class)->settings()?->administrator_user_id)?->email,
            'welcomeTemplates' => config('welcome_templates'),
            'welcomePreview' => app(SystemBranding::class)->template($this->welcomeTemplate, $this->plan),
            'artworkSlots' => SystemBranding::ARTWORK,
            'previewColors' => app(SystemBranding::class)->colors(app(SystemBranding::class)->template($this->welcomeTemplate, $this->plan), $this->welcomeColors),
            'currentLogoUrl' => app(SystemBranding::class)->logoUrl(),
            'grantedModules' => config('central_licensing.enabled')
                ? app(CentralLicenseService::class)->configuredModules()
                : [],
            'plans' => config('mediflow_modules.plans'),
            'features' => config('mediflow_modules.features'),
            'required' => config('mediflow_modules.required'),
            'dependencies' => config('mediflow_modules.dependencies'),
        ]);
    }
}
