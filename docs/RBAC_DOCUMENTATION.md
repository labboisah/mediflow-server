# Hospital Record Management System - Role-Based Access Control (RBAC)

## Overview
This document outlines the Role-Based Access Control (RBAC) system implemented for the Hospital Record Management System (HRMFYH).

## System Roles

### 1. **Administrator**
- **Description**: Full system access with ability to manage all users, roles, and permissions
- **Permissions**: All system permissions
- **Key Responsibilities**:
  - Manage user accounts
  - Create, edit, and delete roles
  - Create, edit, and delete permissions
  - Assign roles and permissions to users
  - View system audit logs

### 2. **Record Officer**
- **Description**: Manages patient medical records and documentation
- **Key Permissions**:
  - View patient records
  - Create patient records
  - Edit patient records
  - Export records
  - View patient list
  - View appointments

### 3. **Nurse**
- **Description**: Manages patient care, vital signs, and treatment notes
- **Key Permissions**:
  - View patient records
  - Record vital signs
  - View vital signs
  - View and record treatment
  - View patient information
  - Access prescriptions and lab tests

### 4. **Midwife**
- **Description**: Manages antenatal care, patient visits, medication administration, labour and delivery support, newborn care, and postnatal follow-up
- **Key Permissions**:
  - View patient records
  - Create and view visits
  - Access patient history
  - Manage prescriptions and drug chart entries
  - Manage admission and discharge related to labour/delivery
  - Document observations and nursing notes

### 5. **Doctor**
- **Description**: Diagnoses patients, prescribes treatments, and manages prescriptions
- **Key Permissions**:
  - View and edit patient records
  - Create and manage appointments
  - Create and approve prescriptions
  - Record diagnosis and treatment
  - Request and view lab tests
  - View vital signs

### 5. **Pharmacist**
- **Description**: Manages medications and pharmacy inventory
- **Key Permissions**:
  - View prescriptions
  - Approve prescriptions
  - View and manage inventory
  - Dispense medications
  - View expiry dates

### 6. **Lab Technician**
- **Description**: Manages laboratory tests and test results
- **Key Permissions**:
  - View lab tests
  - Create lab tests
  - Submit lab results
  - View patient information

### 7. **Accountant**
- **Description**: Manages billing, payments, and financial records
- **Key Permissions**:
  - View billing information
  - Create bills
  - Process payments
  - View financial reports

## Database Structure

### Tables Created

#### `roles` Table
- `id` (Primary Key)
- `name` (Unique) - e.g., 'administrator', 'doctor', 'nurse'
- `display_name` - e.g., 'Administrator', 'Doctor'
- `description` - Role description
- `created_at`, `updated_at`

#### `permissions` Table
- `id` (Primary Key)
- `name` (Unique) - e.g., 'create_records', 'view_patients'
- `display_name` - e.g., 'Create Records'
- `description` - Permission description
- `module` - Permission category (e.g., 'Patient Records', 'Billing')
- `created_at`, `updated_at`

#### `role_permission` Pivot Table
- `id` (Primary Key)
- `role_id` (Foreign Key) - References `roles.id`
- `permission_id` (Foreign Key) - References `permissions.id`
- Composite Unique: `(role_id, permission_id)`
- `created_at`, `updated_at`

#### `role_user` Pivot Table
- `id` (Primary Key)
- `user_id` (Foreign Key) - References `users.id`
- `role_id` (Foreign Key) - References `roles.id`
- Composite Unique: `(user_id, role_id)`
- `created_at`, `updated_at`

## Models

### User Model
**Location**: `app/Models/User.php`

Methods:
- `roles()` - Get all roles for the user
- `permissions()` - Get all permissions through roles
- `hasRole(string|array $role)` - Check if user has specific role
- `hasAnyRole(array $roles)` - Check if user has any of the given roles
- `hasAllRoles(array $roles)` - Check if user has all given roles
- `hasPermission(string|array $permission)` - Check if user has permission
- `hasAnyPermission(array $permissions)` - Check if user has any permission
- `getAllPermissions()` - Get all permissions from all roles
- `assignRole(Role|string $role)` - Assign role to user
- `removeRole(Role|string $role)` - Remove role from user
- `removeAllRoles()` - Remove all roles from user
- `syncRoles(array|string $roles)` - Replace all roles with given roles

### Role Model
**Location**: `app/Models/Role.php`

Methods:
- `users()` - Get all users with this role
- `permissions()` - Get all permissions assigned to role
- `hasPermission(string $permission)` - Check if role has permission
- `grantPermission(Permission|string $permission)` - Assign permission to role
- `revokePermission(Permission|string $permission)` - Remove permission from role

### Permission Model
**Location**: `app/Models/Permission.php`

Properties:
- `name` - Permission identifier (machine readable)
- `display_name` - User-friendly name
- `description` - What the permission allows
- `module` - Module/category

Methods:
- `roles()` - Get roles that have this permission

## Seeders

### RoleSeeder
Creates all 7 roles in the system:
- Administrator
- Record Officer
- Nurse
- Doctor
- Pharmacist
- Lab Technician
- Accountant

**Run**: `php artisan db:seed --class=RoleSeeder`

### PermissionSeeder
Creates all permissions and assigns them to appropriate roles:
- Administration permissions (for Admin only)
- Patient Records permissions
- Patient Management permissions
- Appointment permissions
- Prescription permissions
- Laboratory permissions
- Pharmacy permissions
- Billing permissions
- Clinical permissions

**Run**: `php artisan db:seed --class=PermissionSeeder`

### AdminUserSeeder
Creates an admin user account:
- **Email**: admin@hospital.test
- **Password**: admin@123
- **Role**: Administrator

**Run**: `php artisan db:seed --class=AdminUserSeeder`

### Run All Seeders:
```bash
php artisan db:seed
```

## Middleware

### CheckRole Middleware
**Location**: `app/Http/Middleware/CheckRole.php`

**Usage**:
```php
Route::post('/admin/users', [UserController::class, 'store'])
    ->middleware('role:administrator,record_officer');
```

**In Controller**:
```php
public function store(Request $request)
{
    if (!$request->user()->hasRole('administrator')) {
        abort(403, 'Unauthorized');
    }
    // Your logic here
}
```

### CheckPermission Middleware
**Location**: `app/Http/Middleware/CheckPermission.php`

**Usage**:
```php
Route::post('/patient-records', [RecordController::class, 'store'])
    ->middleware('permission:create_records');
```

**In Controller**:
```php
public function store(Request $request)
{
    if (!$request->user()->hasPermission('create_records')) {
        abort(403, 'You do not have permission to create records');
    }
    // Your logic here
}
```

## Helper Functions

**Location**: `app/Helpers/RolePermissionHelper.php`

### Usage in Controllers:
```php
use App\Helpers\RolePermissionHelper;

if (RolePermissionHelper::hasRole('doctor')) {
    // Doctor-specific logic
}

if (RolePermissionHelper::hasPermission('create_prescriptions')) {
    // Create prescription logic
}

if (RolePermissionHelper::isAdmin()) {
    // Admin-only logic
}
```

### Usage in Blade Templates:
```blade
@if(auth()->user()->hasRole('administrator'))
    <a href="/admin/users">Manage Users</a>
@endif

@if(auth()->user()->hasPermission('create_records'))
    <button>Create Record</button>
@endif

@unless(auth()->user()->hasAllRoles(['doctor', 'administrator']))
    <p>Access Denied</p>
@endunless
```

## Usage Examples

### 1. Check User Role in Controller
```php
public function show(User $user)
{
    if (auth()->user()->hasRole('administrator')) {
        // Admin can see all details
        return view('admin.user-details', ['user' => $user]);
    }

    if (auth()->user()->hasRole('doctor')) {
        // Doctor sees limited details
        return view('doctor.user-details', ['user' => $user]);
    }

    abort(403, 'Unauthorized');
}
```

### 2. Check Multiple Roles
```php
// Check if user has ANY of these roles
if (auth()->user()->hasAnyRole(['administrator', 'doctor'])) {
    // Allow access
}

// Check if user has ALL of these roles
if (auth()->user()->hasAllRoles(['administrator', 'record_officer'])) {
    // Rare case - user has both admin and record officer roles
}
```

### 3. Check Permissions
```php
public function store(Request $request)
{
    if (!$request->user()->hasPermission('create_records')) {
        abort(403, 'You do not have permission to create records');
    }

    // Create record logic
}
```

### 4. Assign Roles to Users
```php
// Assign single role
$user->assignRole('doctor');

// Assign multiple roles
$user->syncRoles(['doctor', 'administrator']);

// Remove role
$user->removeRole('doctor');
```

### 5. Manage Role Permissions (Admin)
```php
// Get a role
$doctorRole = Role::where('name', 'doctor')->first();

// Grant permission to role
$doctorRole->grantPermission('create_prescriptions');

// Revoke permission from role
$doctorRole->revokePermission('manage_users');

// Get all permissions for role
$permissions = $doctorRole->permissions()->get();
```

## Admin User Initial Access

After running migrations and seeders:

1. **Login Credentials**:
   - Email: `admin@hospital.test`
   - Password: `admin@123`

2. **First Steps**:
   - Login to the system
   - Change admin password (recommended)
   - Create additional users
   - Assign roles and permissions

## Security Best Practices

1. **Always check permissions in controllers and routes**
2. **Use middleware to protect sensitive routes**
3. **Validate permissions on both frontend and backend**
4. **Log permission-related actions for audit trails**
5. **Regularly review role assignments**
6. **Principle of Least Privilege**: Assign only necessary permissions
7. **Never trust frontend permission checks alone**

## Registering Middleware (if needed)

In `app/Http/Kernel.php`:
```php
protected $routeMiddleware = [
    // ... existing middleware
    'role' => \App\Http\Middleware\CheckRole::class,
    'permission' => \App\Http\Middleware\CheckPermission::class,
];
```

## Future Enhancements

1. Create admin panel for role/permission management
2. Implement activity logging for permission changes
3. Add email notifications for role assignments
4. Create role templates for quick setup
5. Implement permission hierarchies
6. Add time-based role assignments
7. Create audit trail dashboard

## Support

For questions or issues with the RBAC system, refer to:
- Laravel Documentation: https://laravel.com/docs
- Model Documentation: See model comments above
- Test the system with the provided seeders
