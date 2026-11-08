<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InstallationAdministrator
{
    public function email(string $brand): string
    {
        $label = preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii(trim($brand))));
        if (! $label || strlen($label) > 63) {
            throw ValidationException::withMessages(['brandName' => 'Brand name must produce a domain name of 1 to 63 letters or numbers.']);
        }

        return 'admin@'.$label.'.com';
    }

    public function save(array $input): array
    {
        abort_unless(auth()->user()?->is_installation_admin, 403);

        return DB::transaction(function () use ($input) {
            $settings = SystemSetting::lockForUpdate()->find(1);
            if (! $settings) {
                throw ValidationException::withMessages(['adminEmail' => 'Save system branding before registering the administrator.']);
            }
            $user = $settings->administrator_user_id ? User::withTrashed()->lockForUpdate()->findOrFail($settings->administrator_user_id) : null;
            if ($user && ($user->trashed() || $user->is_partner_only || $user->is_installation_admin)) {
                throw ValidationException::withMessages(['adminEmail' => 'The linked administrator is unavailable. Review it in User Management.']);
            }
            $input['adminEmail'] = strtolower(trim($input['adminEmail'] ?? ''));
            $data = Validator::make($input, [
                'adminName' => ['required', 'string', 'max:255'],
                'adminEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
                'adminPassword' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:200', 'confirmed', 'required_with:adminPassword_confirmation'],
            ])->validate();
            $role = Role::where('name', 'administrator')->firstOrFail();
            $created = ! $user;
            $user ??= new User;
            $before = $created ? null : $user->only(['name', 'email']);
            $user->name = trim($data['adminName']);
            $user->email = $data['adminEmail'];
            $passwordChanged = filled($data['adminPassword'] ?? null);
            if ($passwordChanged) {
                $user->password = $data['adminPassword'];
            }
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            $user->saveQuietly();
            $user->assignRole($role);
            $settings->administrator_user_id = $user->id;
            $settings->save();
            AuditLog::create(['actor_id' => auth()->id(), 'action' => $created ? 'installation.administrator_created' : 'installation.administrator_updated',
                'model_type' => User::class, 'model_id' => $user->id, 'before' => $before, 'after' => $user->only(['name', 'email']),
                'meta' => ['source' => 'registration_form', 'password_changed' => $passwordChanged], 'ip' => request()->ip(), 'user_agent' => request()->userAgent()]);

            return ['email' => $user->email, 'name' => $user->name, 'created' => $created];
        }, 3);
    }
}
