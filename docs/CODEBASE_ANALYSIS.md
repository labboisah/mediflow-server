# Hospital Management System - Codebase Analysis

## 1. CONTROLLER STRUCTURE

### Pattern Overview
The system uses a hierarchical controller structure with role-specific namespacing:

```
app/Http/Controllers/
├── Controller.php (base, empty)
├── Admin/
│   ├── AdminController.php
│   ├── UserController.php
│   ├── RoleController.php
│   ├── PermissionController.php
│   ├── DepartmentController.php
│   ├── WardController.php
│   ├── BedController.php
│   └── InvestigationController.php
├── Patient/
│   ├── PatientController.php
│   ├── AdmissionController.php
│   ├── VitalSignController.php
│   ├── InvestigationController.php
│   ├── IncontinuationController.php
│   ├── DischargeController.php
│   ├── PrescriptionController.php
│   ├── DrugChartController.php
│   ├── FluidBalanceController.php
│   └── ObservationController.php
├── Nurse/
├── Doctor/
├── Lab/
├── Pharmacy/
├── Radiograph/
└── RecordOfficerController.php
```

### CRUD Pattern Example: PatientController

**Structure:**
```php
namespace App\Http\Controllers\Patient;

class PatientController extends Controller
{
    // READ - List
    public function index() {
        return view('nurse.patient.index');
    }

    // READ - Single
    public function show(Patient $patient) {
        return view('patient.show', compact('patient'));
    }

    // READ - History
    public function history(Patient $patient) {
        $visits = $patient->visits()->paginate(10);
        return view('patient.history', compact('patient', 'visits'));
    }

    // SEARCH - Complex Query
    public function search(Request $request) {
        // Searches with 'with' eager loading
        $patients = Patient::with('demographic')
            ->where('hospital_number', 'like', "%{$query}%")
            ->orWhereHas('demographic', function ($q) use ($query) {
                $q->where('phone_number', 'like', "%{$query}%")
                  ->orWhere('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->get();
        return view('patient.search', compact('patients', 'query'));
    }
}
```

### CRUD Pattern Example: AdmissionController

**Structure:**
```php
namespace App\Http\Controllers\Patient;

class AdmissionController extends Controller
{
    // CREATE - Show form
    public function create(Patient $patient) {
        return view('patient.admission.create', compact('patient'));
    }

    // CREATE - Store data
    public function store(Request $request, Patient $patient) {
        $request->validate([
            'bed_id' => 'required',
            'date' => 'required',
            'days' => 'required'
        ]);

        $admission = $patient->currentVisit()->admissions()->create([
            'date' => $request->date,
            'bed_id' => $request->bed_id,
            'note' => $request->note,
            'time' => $request->time,
            'admitted_by' => auth()->user()->id
        ]);

        // Automatic bed assignment and billing
        $bed = Bed::find($request->bed_id);
        $bed->update(['status' => 'occupied']);
        $patient->currentVisit()->generateBedSpaceBill($admission, $bed, $request->days);

        return redirect()->route('patient.show', $patient)
            ->with('success', 'Admission Registered');
    }

    // UPDATE - Confirm admission
    public function confirmed(Admission $admission) {
        $admission->update(['status' => 'confirmed']);
        return redirect()->route('patient.show', $admission->patientVisit->patient)
            ->with('success', 'Patient Admission Confirmed');
    }
}
```

### Key Controller Patterns

1. **Route Model Binding**: Controllers use type-hinted model parameters for automatic resolution
   ```php
   public function show(Patient $patient) // Auto-resolved from route {patient}
   public function update(Request $request, User $user)
   ```

2. **Resource Usage**: Controllers work with Eloquent relationships
   ```php
   $patient->currentVisit()->admissions()->create($data)
   $patient->demographic->full_name
   ```

3. **Validation in Controllers**: Inline validation using `$request->validate()`
   ```php
   $request->validate([
       'bed_id' => 'required',
       'date' => 'required',
       'days' => 'required'
   ]);
   ```

4. **Logging/Auditing**: Important operations logged via `AuditLog::record()`
   ```php
   AuditLog::record(auth()->user(), 'user.update', $user, $before, $after);
   ```

---

## 2. ROLE-BASED PERMISSIONS & ACCESS CONTROL

### Middleware Pattern

**Route-Level Permission Protection** ([web.php](routes/web.php)):
```php
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    
    Route::middleware('role:administrator')->group(function () {
        // Only administrators can access these routes
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
        Route::resource('users', UserController::class);
    });
});

Route::middleware(['auth', 'verified', 'role:record_officer'])->prefix('record-officer')->name('record_officer.')->group(function () {
    // Only record officers can access these routes
    Route::get('/', [RecordOfficerController::class, 'dashboard'])->name('dashboard');
});
```

### Middleware Implementation

**CheckRole Middleware** - Validates user has required role:
```php
namespace App\Http\Middleware;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if ($request->user()->hasAnyRole($roles)) {
            return $next($request);
        }

        return response()->view('errors.403', [], 403);
    }
}
```

**CheckPermission Middleware** - Validates user has required permissions:
```php
namespace App\Http\Middleware;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if ($request->user()->hasAnyPermission($permissions)) {
            return $next($request);
        }

        return response()->view('errors.403', [], 403);
    }
}
```

### System Roles

From RBAC_DOCUMENTATION.md:

| Role | Purpose | Key Permissions |
|------|---------|-----------------|
| **Administrator** | Full system access | All permissions |
| **Record Officer** | Patient registration & records | Create/Edit patient records, view list |
| **Nurse** | Patient vital signs, care | Record vitals, view patient info, access prescriptions |
| **Midwife** | Antenatal, labour, delivery, postnatal | Manage antenatal care, labour, delivery, newborn care |
| **Doctor** | Diagnoses & prescriptions | View/Edit records, create appointments, approve prescriptions |
| **Pharmacist** | Medication management | View prescriptions, dispense, manage inventory |
| **Lab Technician** | Laboratory tests | View tests, create tests, submit results |
| **Accountant** | Billing & payments | View billing, create bills, process payments |

### Helper Class for Permissions

**RolePermissionHelper** - Convenience methods:
```php
namespace App\Helpers;

class RolePermissionHelper
{
    public static function hasRole(string|array $role): bool
    public static function hasAnyRole(array $roles): bool
    public static function hasAllRoles(array $roles): bool
    public static function hasPermission(string|array $permission): bool
    public static function hasAnyPermission(array $permissions): bool
    public static function isAdmin(): bool
}
```

### Controller-Level Permission Check

**Example from UserController.php**:
```php
public function edit(User $user)
{
    // Programmatic check - prevents self-edit
    if ($user->id === auth()->id()) {
        return redirect()->route('admin.users.index')
            ->with('error', 'You cannot edit your own roles here.');
    }
    
    $roles = Role::all();
    $userRoles = $user->roles->pluck('id')->toArray();
    return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
}
```

---

## 3. ROUTING PATTERN

### Main Route Structure

**File Organization** ([routes/](routes/)):
- `web.php` - Main application routes
- `auth.php` - Authentication routes
- `patient.php` - Patient-specific routes
- `doctor.php` - Doctor-specific routes
- `nurse.php` - Nurse-specific routes
- `lab.php` - Laboratory routes
- `pharmacy.php` - Pharmacy routes
- `radiograph.php` - Radiograph routes
- `department.php` - Department management
- `breadcrumbs.php` - Breadcrumb configuration
- `console.php` - Console commands

### Route Nesting Pattern

**Example: Patient Routes** ([patient.php](routes/patient.php)):
```php
Route::name('patient.')
    ->middleware('auth')
    ->namespace('Patient')
    ->prefix('patient')
    ->group(function () {
        // Main patient routes
        Route::get('/', [PatientController::class, 'index'])->name('index');
        Route::get('/{patient}/show', [PatientController::class, 'show'])->name('show');

        // Nested route groups for sub-resources
        Route::name('vitalsign.')
            ->prefix('vital-sign')
            ->group(function () {
                Route::get('/{patient}/create', [VitalSignController::class, 'create'])->name('create');
                Route::post('/{patient}/register', [VitalSignController::class, 'register'])->name('register');
            });

        // Nested admission routes
        Route::name('admission.')
            ->prefix('admission')
            ->group(function () {
                Route::get('/{patient}/create', [AdmissionController::class, 'create'])->name('create');
                Route::post('/{patient}/store', [AdmissionController::class, 'store'])->name('store');
            });

        // Prescription nested routes
        Route::name('prescription.')
            ->prefix('prescription')
            ->group(function () {
                Route::get('/{patient}/create', [PrescriptionController::class, 'create'])->name('create');
                Route::post('/{prescription}/add-medicine', [PrescriptionController::class, 'addMedicine'])->name('add');
            });
    });
```

### Key Routing Conventions

1. **Route Naming**: Hierarchical names for easy route generation
   - `patient.index` - List patients
   - `patient.show` - Show single patient
   - `patient.admission.create` - Create admission form
   - `patient.admission.store` - Store admission

2. **Route Model Binding**: Automatic model resolution
   - `/{patient}` resolves to Patient model
   - `/{admission}` resolves to Admission model

3. **Nested Resource Groups**: Logical organization
   - All vital sign routes under `patient/vital-sign`
   - All admission routes under `patient/admission`

4. **Middleware Application**:
   - Auth middleware applied to entire group
   - Namespace routing for controller organization

---

## 4. REQUEST/VALIDATION CLASSES

### Current Structure

**Available Request Classes** ([app/Http/Requests/](app/Http/Requests/)):
```
Requests/
├── Auth/
│   └── LoginRequest.php
└── ProfileUpdateRequest.php
```

### Example: ProfileUpdateRequest

```php
namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
```

### Current Validation Practice

Most controllers use **inline validation** rather than Form Request classes:

```php
public function register(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'gender' => 'required|in:Male,Female,Other',
        'date_of_birth' => 'required|date',
        'phone_number' => 'required|string|unique:patient_demographics,phone_number',
        'email' => 'nullable|email|unique:patient_demographics,email',
        'nok_name' => 'required|string|max:255',
        'nok_relationship' => 'required|string|max:255',
        'nok_telephone' => 'required|string|max:20',
    ]);
    
    // Process validated data
    $patient = Patient::create([...]);
}
```

### Key Validation Rules Used

| Rule | Purpose |
|------|---------|
| `required` | Field must have value |
| `string\|integer\|date` | Type validation |
| `email` | Must be valid email format |
| `unique:table,column` | Must be unique in database |
| `in:value1,value2` | Must be one of allowed values |
| `exists:table,column` | Must exist in database |
| `confirmed` | Must match field_confirmation field |
| `nullable` | Can be empty or null |
| `max:255\|min:6` | Length validation |

---

## 5. PATIENT DEMOGRAPHICS (Gender, Age, DOB)

### Data Storage Structure

Patient information is split across two models:

#### Patient Model
```php
// app/Models/Patient.php
class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hospital_number',
        'payment_id',
        'registration_date',
        'is_walkIn',
    ];

    public function demographic()
    {
        return $this->hasOne(PatientDemographic::class);
    }
}
```

#### PatientDemographic Model
```php
// app/Models/PatientDemographic.php
class PatientDemographic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'first_name',
        'last_name',
        'gender',           // ← Gender stored here
        'date_of_birth',    // ← DOB stored here
        'age',              // ← Age stored here
        'lga',
        'occupation',
        'marital_status',
        'address',
        'phone_number',
        'email',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // Accessor for full name
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Calculate age from date of birth
    public function calculateAge()
    {
        if ($this->date_of_birth) {
            $this->age = $this->date_of_birth->diffInYears(now());
            return $this->age;
        }
        return null;
    }
}
```

### Accessing Patient Demographics in Code

**From Controller:**
```php
public function showPatient(Patient $patient)
{
    $patient->load([
        'demographic',
        'nextOfKin',
        'visits',
        'appointments',
    ]);
    
    // Access demographics
    $fullName = $patient->demographic->full_name;     // "JOHN DOE"
    $gender = $patient->demographic->gender;           // "Male", "Female", "Other"
    $age = $patient->demographic->age;                 // 45
    $dob = $patient->demographic->date_of_birth;      // Carbon date object
    
    return view('record_officer.patient.show', compact('patient'));
}
```

**From View (Blade):**
```blade
<!-- patient/admission/create.blade.php -->
<div class="d-flex align-items-center gap-3">
    <h1 class="h3 mb-1">{{ $patient->demographic->full_name ?? 'Patient Details' }}</h1>
    <p class="mb-0 text-muted">
        Hospital Number: <strong>{{ $patient->hospital_number }}</strong>
    </p>
</div>

<!-- Gender-based conditionals (if needed) -->
@if($patient->demographic->gender === 'Female')
    <!-- Show female-specific options (e.g., antenatal care) -->
    <a href="{{ route('path.to.midwife.services') }}">Antenatal Care</a>
@endif

<!-- Age-based conditionals -->
@if($patient->demographic->age >= 18)
    <!-- Adult-specific treatment -->
@else
    <!-- Pediatric-specific treatment -->
@endif
```

### Patient Registration Workflow

**RecordOfficerController.php** shows the registration process:
```php
public function register(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'gender' => 'required|in:Male,Female,Other',
        'date_of_birth' => 'required|date',
        'phone_number' => 'required|string|unique:patient_demographics,phone_number',
        // ... more fields
    ]);

    try {
        DB::beginTransaction();

        // 1. Create patient record
        $patient = Patient::create([
            'hospital_number' => Patient::generateHospitalNumber(),
            'registration_date' => now(),
            'is_walkIn' => $validated['is_walkIn'] ?? false,
        ]);

        // 2. Calculate age from DOB
        $age = Carbon::parse($validated['date_of_birth'])->age;

        // 3. Create demographic record
        PatientDemographic::create([
            'patient_id' => $patient->id,
            'first_name' => strtoupper($validated['first_name']),
            'last_name' => strtoupper($validated['last_name']),
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'age' => $age,
            // ... more fields
        ]);

        // 4. Create next of kin record
        NextOfKin::create([
            'patient_id' => $patient->id,
            'name' => $validated['nok_name'],
            // ... more fields
        ]);

        DB::commit();
        return redirect()->route('record_officer.patients.show', $patient->id)
            ->with('success', "Patient registered successfully...");
            
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Failed to register patient.'])->withInput();
    }
}
```

### Search Capability

**Search uses demographic relationships:**
```php
$patients = Patient::with('demographic')  // Eager load
    ->where('hospital_number', 'like', "%{$query}%")
    ->orWhereHas('demographic', function ($q) use ($query) {
        // Search across demographic fields
        $q->where('phone_number', 'like', "%{$query}%")
          ->orWhere('first_name', 'like', "%{$query}%")
          ->orWhere('last_name', 'like', "%{$query}%");
    })
    ->get();
```

### Gender Values

Gender is stored as a string with three allowed values:
- `Male`
- `Female`
- `Other`

Validation enforces this:
```php
'gender' => 'required|in:Male,Female,Other',
```

---

## 6. VIEW PATTERNS FOR CONDITIONAL DISPLAY

### Blade Conditional Syntax

While full blade examples showing `@can`, `@role`, etc. were not found, the system supports standard Laravel patterns:

### Bootstrap Card-Based UI

**Standard View Pattern** ([record_officer\visit\create.blade.php](resources/views/record_officer/visit/create.blade.php)):
```blade
@extends('layouts.app')

@section('title', 'Record Patient Visit - ' . $patient->demographic->full_name)

@section('header')
<div class="d-flex align-items-center gap-3">
    <i class="bi bi-hospital text-success" style="font-size: 2rem;"></i>
    <div>
        <h1 class="h3 mb-1">Record Patient Visit</h1>
        <p class="mb-0 text-muted">For: <strong class="text-success">{{ $patient->demographic->full_name ?? 'Unknown' }}</strong></p>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-6 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-file-medical me-2"></i>Visit Information</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('record_officer.visits.store', $patient->id) }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="visit_date" class="form-label">Visit Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('visit_date') is-invalid @enderror" 
                               id="visit_date" name="visit_date" value="{{ old('visit_date', date('Y-m-d')) }}" required>
                        @error('visit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2 pt-3 border-top">
                        <button type="submit" class="btn btn-success flex-grow-1">
                            <i class="bi bi-check-circle me-2"></i>Record Visit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
```

### Admin View with Actions (users/index.blade.php)

```blade
@extends('layouts.app')

@section('header')
<h1 class="h3 d-flex align-items-center mb-0">
    <i class="bi bi-people-fill me-2 text-warning"></i>
    Manage Users
</h1>
<div class="ms-auto d-flex">
    <!-- Search, filters, and create button -->
    <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-success ms-3">
        <i class="bi bi-plus-circle me-1"></i>New User
    </a>
</div>
@endsection

@section('content')
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @forelse ($user->roles as $role)
                                <span class="badge bg-info">{{ $role->name }}</span>
                            @empty
                                <span class="text-muted">No roles</span>
                            @endforelse
                        </td>
                        <td>
                            @if($user->trashed())
                                <!-- Show restore button -->
                                <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </form>
                            @else
                                <!-- Edit and delete buttons -->
                                @if ($user->id !== auth()->id())
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No users found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
```

### Error Display Pattern

```blade
@if ($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($message = Session::get('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
```

### Form Validation Error Display

```blade
<input type="date" 
       class="form-control @error('visit_date') is-invalid @enderror" 
       id="visit_date" name="visit_date" required>
@error('visit_date')
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
```

### Admission Form with AJAX (patient/admission/create.blade.php)

```blade
<div class="form-group mb-2">
    <label for="ward_id">Ward</label>
    <select name="ward_id" id="ward_id" class="form-control" required>
        <option value="">Select Ward</option>
        @foreach(App\Models\Ward::all() as $ward)
            <option value="{{ $ward->id }}">{{ $ward->name }}</option>
        @endforeach
    </select>
</div>

<div class="form-group mb-2">
    <label for="bed_id">Bed Number</label>
    <select name="bed_id" id="bed_id" class="form-control" required>
        <option value="">Select Bed</option>
    </select>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wardSelect = document.getElementById('ward_id');
    const bedSelect = document.getElementById('bed_id');
    const ajaxBaseUrl = "{{ url('/ajax/beds') }}";

    wardSelect.addEventListener('change', function () {
        const wardId = this.value;
        bedSelect.innerHTML = '<option value="" selected>Select Bed</option>';
        
        if (!wardId) return;

        fetch(`${ajaxBaseUrl}/${wardId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Populate beds dynamically
        });
    });
});
</script>
```

---

## SUMMARY TABLE

| Aspect | Pattern |
|--------|---------|
| **Base Controller** | Empty abstract base at `app/Http/Controllers/Controller.php` |
| **CRUD Operations** | Separate create(), store(), edit(), update(), destroy() methods |
| **Model Binding** | Type-hinted model parameters for automatic resolution |
| **Validation** | Inline `$request->validate()` in controllers (some FormRequest classes) |
| **Permission Check** | Middleware `role:rolename` and `permission:permissionname` |
| **Role-Based Routes** | Wrapped in `Route::middleware('role:...')` groups |
| **Eager Loading** | Using `Model::with(['relationship'])` |
| **Error Handling** | Try-catch with DB transactions, session flash messages |
| **Views** | Bootstrap 5 cards, icons from Bootstrap Icons library |
| **Patient Gender** | `PatientDemographic::gender` enum: Male, Female, Other |
| **Patient Age** | Calculated from DOB: `date_of_birth->diffInYears(now())` |
| **Patient DOB** | Stored in `PatientDemographic::date_of_birth` (date cast) |
| **Search** | Using `where/orWhere` with `whereHas` for relationships |
| **Dynamic Forms** | AJAX to fetch related data (e.g., beds by ward) |

