<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Auditable;
use App\Models\Traits\Reportable;
use Carbon\Carbon;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use SoftDeletes, Auditable, Reportable;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_installation_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function pendingServiceRequests() {
        $requests = [];

        foreach($this->department->services as $service) {
            foreach($service->serviceRequests as $req) {
                if($req->patientVisit && $req->patientVisit->status == 'Active'){
                    $requests[] = $req;
                }
            }
        }
        return $requests;
    }

    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function bills() {
        return $this->hasMany(Bill::class, 'issued_by');
    }

    /**
     * Get the roles this user has.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function moduleAccess(): HasMany
    {
        return $this->hasMany(ModuleUserAccess::class);
    }

    public function hasModuleAccess(string $module): bool
    {
        return app(\App\Services\LicenseService::class)->userHasModuleAccess($this, $module);
    }

    /**
     * Get all permissions through roles.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->through('role_user');
    }

    /**
     * Get temporary permissions for this user.
     */
    public function temporaryPermissions()
    {
        return $this->hasMany(TemporaryPermission::class)->active();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string|array $role): bool
    {
        $roles = is_array($role) ? $role : [$role];
        if ($this->usesStrictEntitlementEnforcement()) {
            $catalog = app(\App\Services\InstallationCatalog::class);
            $roles = array_values(array_filter(
                $roles,
                fn ($candidate) => is_string($candidate) && $catalog->roleAllowed($candidate),
            ));
        }

        if (in_array('head_of_department', $roles, true) && $this->isDepartmentHead()) {
            return true;
        }

        if ($roles === []) {
            return false;
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function isDepartmentHead(): bool
    {
        if ($this->usesStrictEntitlementEnforcement()
            && ! app(\App\Services\InstallationCatalog::class)->roleAllowed('head_of_department')) {
            return false;
        }

        if (! $this->department_id || ! $this->department) return false;
        $specificRole = 'head_of_'.\Illuminate\Support\Str::slug($this->department->name, '_');
        return $this->roles()->whereIn('name', ['head_of_department', $specificRole])->exists();
    }

    /**
     * Check if user has all the given roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        if ($this->usesStrictEntitlementEnforcement()) {
            $catalog = app(\App\Services\InstallationCatalog::class);
            $allowed = array_filter($roles, fn ($role) => $catalog->roleAllowed($role));
            if (count($allowed) !== count($roles)) {
                return false;
            }
        }

        return $this->roles()->whereIn('name', $roles)->count() === count($roles);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string|array $permission): bool
    {
        $permissions = $this->getAllPermissions();
        if ($this->usesStrictEntitlementEnforcement()) {
            $entitlements = app(\App\Services\ProductEntitlementService::class);
            $permissions = $permissions->filter(fn ($assigned) => $entitlements->permissionAllowed($assigned));
        }

        if (is_array($permission)) {
            return $permissions->whereIn('name', $permission)->count() === count($permission);
        }

        return $permissions->contains('name', $permission);
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $assigned = $this->getAllPermissions();
        if ($this->usesStrictEntitlementEnforcement()) {
            $entitlements = app(\App\Services\ProductEntitlementService::class);
            $assigned = $assigned->filter(fn ($permission) => $entitlements->permissionAllowed($permission));
        }

        return $assigned->whereIn('name', $permissions)->count() > 0;
    }

    private function usesStrictEntitlementEnforcement(): bool
    {
        return (bool) config('central_licensing.enabled')
            && (bool) config('entitlement_framework.strict_enforcement', false);
    }

    /**
     * Get all permissions for the user from all roles and temporary permissions.
     */
    public function getAllPermissions()
    {
        $permissions = collect();

        // Get permissions from roles
        foreach ($this->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }

        // Add temporary permissions if they're still active
        foreach ($this->temporaryPermissions as $tempPerm) {
            if ($tempPerm->isValid()) {
                $permissions->push($tempPerm->permission);
            }
        }

        return $permissions->unique('id');
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        if (! $this->roles()->where('roles.id', $role->id)->exists()) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Remove all roles from the user.
     */
    public function removeAllRoles(): void
    {
        $this->roles()->detach();
    }

    /**
     * Sync roles for the user (replaces all existing roles).
     */
    public function syncRoles(array|string $roles): void
    {
        
        $roleIds = collect($roles)->map(function ($role) {
            return $role;
        })->toArray();
        
        $this->roles()->sync($roleIds);
    }

    public function getModels()
    {
        $modelsPath = app_path('Models');

        $models = [];

        foreach (scandir($modelsPath) as $file) {

            // Skip dots
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Only PHP files
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            // Remove .php extension
            $modelName = pathinfo($file, PATHINFO_FILENAME);

            // Full namespace
        $models[] = ["name"=>$modelName, "class"=>"App\\Models\\{$modelName}",'table'=>(new ("App\\Models\\{$modelName}"))->getTable()];
        }

        return $models;
    }

    public function getMidwifeData() {
        return [
            // Antenatal Care Statistics
            'antenatal_total' => AntenatalCare::count(),
            'antenatal_today' => AntenatalCare::whereDate('created_at', today())->count(),
            'antenatal_this_month' => AntenatalCare::whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])->count(),
            'pregnant_patients' => Patient::whereHas('antenatalCares')->count(),
            
            // Labour Statistics
            'labour_total' => Labour::count(),
            'labour_today' => Labour::whereDate('created_at', today())->count(),
            'labour_this_month' => Labour::whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])->count(),
            'labour_in_progress' => Labour::where('status', 'in_progress')->count(),
            'labour_completed' => Labour::where('status', 'completed')->count(),
            
            // Delivery Statistics
            'delivery_total' => Delivery::count(),
            'delivery_today' => Delivery::whereDate('created_at', today())->count(),
            'delivery_this_month' => Delivery::whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])->count(),
            'vaginal_deliveries' => Delivery::where('delivery_type', 'vaginal')->count(),
            'caesarean_deliveries' => Delivery::where('delivery_type', 'caesarean')->count(),
            
            // Newborn Statistics
            'newborn_total' => Newborn::count(),
            'newborn_today' => Newborn::whereDate('created_at', today())->count(),
            'newborn_males' => Newborn::where('sex', 'male')->count(),
            'newborn_females' => Newborn::where('sex', 'female')->count(),
            'newborn_healthy' => Newborn::where('status', 'healthy')->count(),
            'newborn_at_risk' => Newborn::where('status', 'at_risk')->count(),
            
            // Examination Statistics
            'newborn_examinations_total' => NewbornExamination::count(),
            'postnatal_examinations_total' => PostnatalExamination::count(),
            'postnatal_normal' => PostnatalExamination::where('recovery_status', 'normal')->count(),
            'postnatal_at_risk' => PostnatalExamination::where('recovery_status', 'at_risk')->count(),
            'child_follow_ups_total' => ChildFollowUp::count(),
            'child_follow_ups_today' => ChildFollowUp::whereDate('created_at', today())->count(),
            
            // Recent Records
            'recent_antenatal' => AntenatalCare::with('patient')
                ->latest()
                ->limit(5)
                ->get(),
            'recent_deliveries' => Delivery::with('patient')
                ->latest()
                ->limit(5)
                ->get(),
            'recent_newborns' => Newborn::with('delivery.patient')
                ->latest()
                ->limit(5)
                ->get(),
            'recent_follow_ups' => ChildFollowUp::with('newborn')
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }
}
