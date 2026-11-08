<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\InstallationOnboarding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use KernelBridge\LicensingClient\Services\LicenseCacheService;

class InstallationOnboardingController extends Controller
{
    public function show(Request $request, InstallationOnboarding $onboarding, LicenseCacheService $cache)
    {
        if (! $cache->hasUsableLicense()) {
            return redirect()->route('kernelbridge.license.show');
        }
        $onboarding->authorize($request);
        if ($onboarding->completed()) {
            return redirect()->route('admin.installation');
        }

        return view('installation.setup', [
            'newAdministrator' => ! $onboarding->hasAdministrator(),
            'settings' => SystemSetting::find(1),
            'configuration' => $cache->state()->entitlement_payload['configuration'] ?? [],
            'templates' => config('welcome_templates'),
        ]);
    }

    public function store(Request $request, InstallationOnboarding $onboarding, LicenseCacheService $cache)
    {
        $onboarding->authorize($request);
        abort_if($onboarding->completed(), 409, 'Installation setup is already complete.');
        $newAdministrator = ! $onboarding->hasAdministrator();
        $rules = [
            'brand_name' => ['required', 'string', 'max:120'], 'address' => ['nullable', 'string', 'max:255'],
            'welcome_heading' => ['nullable', 'string', 'max:180'], 'welcome_statement' => ['nullable', 'string', 'max:1500'],
            'welcome_template' => ['required', Rule::in(array_merge(['auto'], array_keys(config('welcome_templates'))))],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=2048,max_height=2048'],
        ];
        if ($newAdministrator) {
            $rules += ['admin_name' => ['required', 'string', 'max:255'],
                'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12', 'max:200', 'confirmed']];
        }
        $data = $request->validate($rules);
        $logo = $request->file('logo')?->store('branding', 'local');
        try {
            $admin = DB::transaction(function () use ($request, $onboarding, $cache, $data, $logo): User {
                $state = DB::table('installation_onboarding')->where('id', 1)->lockForUpdate()->first();
                abort_if(! $state || $state->completed_at, 409, 'Installation setup is already complete.');
                $onboarding->authorize($request);
                $admin = $request->user();
                if (! $onboarding->hasAdministrator()) {
                    $admin = new User(['name' => $data['admin_name'], 'email' => strtolower($data['admin_email']), 'password' => $data['password']]);
                    $admin->is_installation_admin = true;
                    $admin->saveQuietly();
                    $role = Role::withoutEvents(fn () => Role::firstOrCreate(['name' => 'administrator'], ['display_name' => 'Administrator']));
                    $admin->assignRole($role);
                }
                abort_unless($admin?->is_installation_admin, 403);
                $settings = SystemSetting::find(1) ?? new SystemSetting;
                $settings->id = 1;
                $settings->fill(collect($data)->only(['brand_name', 'address', 'welcome_heading', 'welcome_statement', 'welcome_template'])->all());
                if ($logo) {
                    $settings->logo_path = $logo;
                }
                $settings->save();
                DB::table('installation_onboarding')->where('id', 1)->update([
                    'completed_at' => now(), 'completed_by' => $admin->id,
                    'license_identifier' => $cache->state()->license_identifier,
                ]);

                return $admin;
            });
        } catch (\Throwable $exception) {
            if ($logo) {
                Storage::disk('local')->delete($logo);
            }
            throw $exception;
        }
        Auth::login($admin);
        $request->session()->forget('installation_activation');
        $request->session()->regenerate();

        return redirect('/')->with('status', 'Installation complete. Your welcome page and licensed workspaces are ready.');
    }
}
