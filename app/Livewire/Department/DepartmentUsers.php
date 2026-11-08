<?php

namespace App\Livewire\Department;

use App\Models\AuditLog;
use App\Models\ModuleUserAccess;
use App\Models\Role;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class DepartmentUsers extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 15;
    public bool $showEditor = false;
    public ?int $editingUserId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public array $selectedRoleIds = [];
    public string $feedback = '';

    public function boot(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->department_id && $user->isDepartmentHead(), 403);
        abort_unless(app(LicenseService::class)->moduleEnabled('access_control'), 403);
    }

    private function allowedRoles()
    {
        $department = strtolower((string) auth()->user()->department?->name);
        $names = [];
        foreach (config('department_staff.departments') as $keyword => $roles) {
            if (str_contains($department, $keyword)) {
                $names = array_merge($names, $roles);
            }
        }
        $license = app(LicenseService::class);
        $names = array_filter(array_unique($names), function ($role) use ($license) {
            foreach (config("department_staff.roles.$role", []) as $module) {
                if (! $license->moduleEnabled($module)) return false;
            }
            return true;
        });
        return Role::whereIn('name', $names)->orderBy('display_name')->get();
    }

    private function departmentUserQuery()
    {
        $allowed = $this->allowedRoles()->pluck('name')->all();
        return User::query()->where('department_id', auth()->user()->department_id)
            ->where('id', '!=', auth()->id())->where('is_installation_admin', false)
            ->whereDoesntHave('roles', fn ($query) => $query->whereNotIn('name', $allowed));
    }

    public function create(): void
    {
        $this->resetEditor();
        $this->showEditor = true;
    }

    public function edit(int $userId): void
    {
        $user = $this->departmentUserQuery()->find($userId);
        abort_unless($user, 404);
        $this->resetEditor();
        $this->showEditor = true;
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->selectedRoleIds = $user->roles()->pluck('roles.id')->map(fn ($id) => (string) $id)->all();
    }

    public function save(): void
    {
        // Recheck both the target and assignable roles on every request.
        $user = $this->editingUserId ? $this->departmentUserQuery()->find($this->editingUserId) : new User;
        abort_unless($user, 404);
        $roles = $this->allowedRoles();
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => [$user->exists ? 'nullable' : 'required', 'string', 'min:8'],
            'passwordConfirmation' => ['required_with:password', 'same:password'],
            'selectedRoleIds' => ['required', 'array', 'min:1'],
            'selectedRoleIds.*' => ['integer', 'distinct', Rule::in($roles->pluck('id')->all())],
        ]);
        DB::transaction(function () use ($user, $validated, $roles) {
            $before = $user->exists ? ['name' => $user->name, 'email' => $user->email, 'roles' => $user->roles()->pluck('name')->all()] : null;
            $user->fill(['name' => $validated['name'], 'email' => $validated['email'], 'department_id' => auth()->user()->department_id]);
            if ($validated['password'] !== '') $user->password = $validated['password'];
            $user->save();
            $user->roles()->sync($validated['selectedRoleIds']);
            $selectedNames = $roles->whereIn('id', $validated['selectedRoleIds'])->pluck('name');
            $modules = $selectedNames->flatMap(fn ($name) => config("department_staff.roles.$name", []))->unique();
            $managedModules = $roles->flatMap(fn ($role) => config("department_staff.roles.$role->name", []))->unique();
            // Only replace module grants within the department's operational scope.
            $user->moduleAccess()->whereIn('license_module', $managedModules)->whereNotIn('license_module', $modules)->update(['is_active' => false]);
            foreach ($modules as $module) {
                ModuleUserAccess::updateOrCreate(['user_id' => $user->id, 'license_module' => $module], [
                    'granted_by' => auth()->id(), 'is_active' => true, 'starts_at' => null, 'expires_at' => null,
                ]);
            }
            AuditLog::create([
                'actor_id' => auth()->id(), 'action' => $before ? 'department.user_updated' : 'department.user_created',
                'model_type' => User::class, 'model_id' => $user->id, 'before' => $before,
                'after' => ['name' => $user->name, 'email' => $user->email, 'roles' => $selectedNames->values()->all(), 'department_id' => $user->department_id, 'password_changed' => $validated['password'] !== ''],
                'ip' => request()->ip(), 'user_agent' => request()->userAgent(),
            ]);
        });
        $this->resetEditor();
        $this->feedback = 'Department user saved.';
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function cancelEdit(): void { $this->resetEditor(); }

    private function resetEditor(): void
    {
        $this->resetValidation();
        $this->reset('showEditor', 'editingUserId', 'name', 'email', 'password', 'passwordConfirmation', 'selectedRoleIds');
    }

    public function render()
    {
        return view('components.department.department-users', [
            'roles' => $this->allowedRoles(),
            'users' => $this->departmentUserQuery()->with(['department', 'roles'])
                ->when(trim($this->search) !== '', function ($query) {
                    $search = trim($this->search);
                    $query->where(fn ($query) => $query->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%"));
                })->orderBy('name')->paginate(in_array($this->perPage, [15, 25, 50], true) ? $this->perPage : 15),
        ]);
    }
}
