<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\InvestigationController;
use App\Http\Controllers\Admin\TemporaryPermissionController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\RecordOfficerController;
use App\Http\Controllers\AccountantController;
use App\Http\Controllers\VitalSignsController;
use App\Http\Controllers\Admin\BedController;
use App\Http\Controllers\Admin\WardController;
use App\Http\Controllers\Admin\BillController;
use App\Http\Controllers\Admin\BillServiceController;
use App\Http\Controllers\Admin\BillInvestigationController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\PatientRegisterController as AdminPatientRegisterController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\AccessControlManager;
use App\Livewire\Admin\BillManagement;
use App\Livewire\Admin\ExpenseCategoryManagement;
use App\Livewire\Admin\ExpenseManagement;
use App\Livewire\Admin\FileTypeManagement;
use App\Livewire\Admin\PaymentManagement;
use App\Livewire\Admin\RevenueCategoryManagement;
use App\Livewire\Admin\RevenueManagement;
use App\Livewire\Accountant\BillWorkspace;
use App\Livewire\Accountant\PaymentWorkspace;
use App\Livewire\Patient\PatientRegistration;
use App\Livewire\Dashboard as UserDashboard;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\SyncronizationController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\FinanceReportDownloadController;
use App\Http\Controllers\MedicalDirector\AdmissionManagementController;
use App\Http\Controllers\WifiSharingController;
use App\Services\WifiSharingService;


Route::get('/branding/logo', [\App\Http\Controllers\BrandingController::class, 'logo'])->name('branding.logo');

Route::get('/branding/image/{slot}', [\App\Http\Controllers\BrandingController::class, 'image'])->whereIn('slot', ['background', 'hero', 'supporting'])->name('branding.image');

// ajax routes
Route::get('/ajax/investigations/{typeId}', [App\Http\Controllers\AjaxController::class, 'getInvestigations'])->name('ajax.get-investigations');
Route::get('/ajax/beds/{wardId}', [App\Http\Controllers\AjaxController::class, 'getWardBeds'])->name('ajax.get-ward-beds');
Route::get('/ajax/medicines/{medicineTypeId}', [App\Http\Controllers\AjaxController::class, 'getMedicines'])->name('ajax.get-type-medicines');
Route::get('/ajax/state/{stateId}/get-lgas', [App\Http\Controllers\AjaxController::class, 'getLgas'])->name('ajax.get-lgas');

Route::get('/', function (Illuminate\Http\Request $request, WifiSharingService $wifiSharing) {
    $branding = app(\App\Services\SystemBranding::class);
    $settings = $branding->settings();
    return view($settings ? 'welcome-installation' : 'welcome', [
        'branding' => $branding,
        'settings' => $settings,
        'template' => $branding->template($settings?->welcome_template ?? 'auto'),
        'wifiSharing' => $wifiSharing->status(),
        'canManageWifiSharing' => $wifiSharing->canManage($request),
    ]);
});

Route::get('/wifi-sharing/status', [WifiSharingController::class, 'status'])->name('wifi-sharing.status');
Route::post('/wifi-sharing/connect', [WifiSharingController::class, 'connect'])->name('wifi-sharing.connect');

Route::get('/dashboard', UserDashboard::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:administrator,medical_director,doctor,nurse,midwife,record,accountant,lab_scientist,lab_technician,radiologist,radiographer,pharmacist,head_of_department')
        ->prefix('/report')
        ->name('report.')
        ->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::post('/generate', [ReportsController::class, 'generate'])->name('generate');
        Route::get('/show', [ReportsController::class, 'show'])->name('show');
    });
});

// Admin Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->middleware('role:administrator,medical_director')->name('index');
    
    Route::get('access-control', AccessControlManager::class)
        ->middleware('access:role:administrator,permission:role.read,permission:permission.read')
        ->name('access-control');

    Route::middleware('role:administrator,medical_director')->group(function () {
        Route::get('patient-register', [AdminPatientRegisterController::class, 'index'])->name('patient-register.index');
        Route::get('patient-register/csv', [AdminPatientRegisterController::class, 'csv'])->name('patient-register.csv');
        Route::get('patient-register/pdf', [AdminPatientRegisterController::class, 'pdf'])->name('patient-register.pdf');
        Route::get('patient-register/{patient}/summary', [AdminPatientRegisterController::class, 'summary'])->name('patient-register.summary');

        Route::get('admissions', [AdmissionManagementController::class, 'index'])->name('admissions.index');
        Route::post('admissions/{admission}/discharge', [AdmissionManagementController::class, 'discharge'])->name('admissions.discharge');
        Route::post('admissions/{admission}/sama', [AdmissionManagementController::class, 'recordSama'])->name('admissions.sama');

        Route::resource('departments', DepartmentController::class);

        Route::resource('wards', WardController::class);

        Route::resource('investigations', InvestigationController::class);

        Route::prefix('wards/beds')->name('beds.')->group(function () {
            Route::get('/{ward}', [BedController::class, 'index'])->name('index');
            Route::get('/{ward}/create', [BedController::class, 'create'])->name('create');
            Route::post('/{ward}/store', [BedController::class, 'store'])->name('store');
            Route::get('/{bed}/edit', [BedController::class, 'edit'])->name('edit');
            Route::put('/{bed}/update', [BedController::class, 'update'])->name('update');
            Route::delete('/{bed}/destroy', [BedController::class, 'destroy'])->name('destroy');
        });

        Route::get('expenses/pdf', [FinanceReportDownloadController::class, 'expenses'])->name('expenses.pdf');
        Route::get('expenses', ExpenseManagement::class)->name('expenses.index');
        Route::get('revenues/pdf', [FinanceReportDownloadController::class, 'revenues'])->name('revenues.pdf');
        Route::get('revenues', RevenueManagement::class)->name('revenues.index');

        Route::prefix('bills')->name('bills.')->group(function () {
            Route::get('/', BillManagement::class)->name('index');
            Route::get('/{bill}', [BillController::class, 'show'])->name('show');
        });

        Route::resource('services', ServiceController::class);
        Route::put('services/{service}/restore', [ServiceController::class, 'restore'])->name('services.restore');
        Route::get('file-types', FileTypeManagement::class)->name('file-types.index');
    });

    Route::middleware('role:administrator')->group(function () {
        // Roles Management
        Route::resource('roles', RoleController::class);
        
        // Permissions Management
        Route::resource('permissions', PermissionController::class);
        
        // Users Management (full resource)
        Route::resource('users', UserController::class);

        Route::resource('expenses', ExpenseController::class)->except(['index', 'show']);
        Route::resource('revenues', RevenueController::class)->except(['index', 'show']);

        Route::get('expense-categories', ExpenseCategoryManagement::class)->name('expense-categories.index');
        Route::get('revenue-categories', RevenueCategoryManagement::class)->name('revenue-categories.index');
        Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('backup', [BackupController::class, 'store'])->name('backup.store');


        // bills management
        Route::prefix('bills')->name('bills.')->group(function () {
            Route::get('/{bill}/edit', [BillController::class, 'edit'])->name('edit');  
            Route::put('/{bill}', [BillController::class, 'update'])->name('update');
            Route::delete('/{bill}', [BillController::class, 'destroy'])->name('delete');

            // investigation management within bills
            Route::get('/{bill}/investigations/create', [BillInvestigationController::class, 'create'])->name('investigations.create');
            Route::put('/investigation/{billInvestigation}', [BillInvestigationController::class, 'update'])->name('investigations.update');
            Route::delete('/investigation/{billInvestigation}', [BillInvestigationController::class, 'destroy'])->name('investigations.destroy');
            Route::get('/investigation/{billInvestigation}/edit', [BillInvestigationController::class, 'edit'])->name('investigations.edit');
            Route::post('/{bill}/investigations/', [BillInvestigationController::class, 'store'])->name('investigations.store');

            // service management within bills
            Route::get('/{bill}/service/create', [BillServiceController::class, 'create'])->name('services.create');
            Route::get('/service/{billService}/edit', [BillServiceController::class, 'edit'])->name('services.edit');
            Route::put('/service/{billService}/update', [BillServiceController::class, 'update'])->name('services.update');
            Route::post('/{bill}/services/store', [BillServiceController::class, 'store'])->name('services.store');
            Route::delete('/service/{billService}/destroy', [BillServiceController::class, 'destroy'])->name('services.destroy');

        });

        Route::put('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');

        // Temporary Permissions Management
        Route::get('temporary-permissions', [TemporaryPermissionController::class, 'index'])->name('temporary-permissions.index');
        Route::get('temporary-permissions/create', [TemporaryPermissionController::class, 'create'])->name('temporary-permissions.create');
        Route::post('temporary-permissions', [TemporaryPermissionController::class, 'store'])->name('temporary-permissions.store');
        Route::put('temporary-permissions/{temporaryPermission}/revoke', [TemporaryPermissionController::class, 'revoke'])->name('temporary-permissions.revoke');
        Route::delete('temporary-permissions/{temporaryPermission}', [TemporaryPermissionController::class, 'destroy'])->name('temporary-permissions.destroy');

        // Services Management
    });

    Route::get('/system/update', [SystemUpdateController::class, 'index'])
        ->middleware('role:administrator')
        ->name('system.update');

    Route::post('/system/update/run', [SystemUpdateController::class, 'update'])
        ->middleware('role:administrator')
        ->name('system.update.run');

    Route::get('/sync/dashboard', [SyncronizationController::class, 'index'])
        ->middleware('role:administrator')
        ->name('sync.dashboards');
});

// Record Officer Routes - Patient Registration and Visit Recording
Route::middleware(['auth', 'verified', 'role:record'])->prefix('record')->name('record.')->group(function () {
    Route::get('/', [RecordOfficerController::class, 'dashboard'])->name('dashboard');

    Route::get('patient-register', [RecordOfficerController::class, 'patientRegister'])->name('patient-register.index');
    Route::get('patient-register/csv', [RecordOfficerController::class, 'patientRegisterCsv'])->name('patient-register.csv');
    Route::get('patient-register/pdf', [RecordOfficerController::class, 'patientRegisterPdf'])->name('patient-register.pdf');
    
    // Patient Management
    Route::get('appointments', [RecordOfficerController::class, 'appointments'])->name('appointments.index');

    Route::get('patients', [RecordOfficerController::class, 'listPatients'])->name('patients.index');
    Route::get('patients/search', [RecordOfficerController::class, 'search'])->name('patients.search');
    Route::get('patients/register', PatientRegistration::class)->name('patients.register.form');
    Route::post('patients/register', [RecordOfficerController::class, 'register'])->name('patients.register');
    Route::get('patients/{patient}', [RecordOfficerController::class, 'showPatient'])->name('patients.show');
    Route::get('patients/{patient}/edit', [RecordOfficerController::class, 'editForm'])->name('patients.edit.form');
    Route::put('patients/{patient}', [RecordOfficerController::class, 'update'])->name('patients.update');
    
    // Patient Visits - Submit to Nurse for Vital Signs
    Route::get('patients/{patient}/visits/create', [RecordOfficerController::class, 'visitForm'])->name('visits.create.form');
    Route::post('patients/{patient}/visits', [RecordOfficerController::class, 'storeVisit'])->name('visits.store');
    
    // Appointments
    Route::get('patients/{patient}/appointments/create', [RecordOfficerController::class, 'appointmentForm'])->name('appointments.create');
    Route::post('patients/{patient}/appointments', [RecordOfficerController::class, 'storeAppointment'])->name('appointments.store');

    // Referrals
    Route::get('patients/{patient}/referrals/create', [RecordOfficerController::class, 'referralForm'])->name('referrals.create');
    Route::post('patients/{patient}/referrals', [RecordOfficerController::class, 'storeReferral'])->name('referrals.store');

    // Admissions and discharges
    Route::get('patients/{patient}/admissions/create', [RecordOfficerController::class, 'admissionForm'])->name('admissions.create');
    Route::post('patients/{patient}/admissions', [RecordOfficerController::class, 'storeAdmission'])->name('admissions.store');
    Route::get('patients/{patient}/discharges/create', [RecordOfficerController::class, 'dischargeForm'])->name('discharges.create');
    Route::post('patients/{patient}/discharges', [RecordOfficerController::class, 'storeDischarge'])->name('discharges.store');
    
    // Workflow - Create st (for dual-role users)
    Route::get('patients/bills/create', [RecordOfficerController::class, 'createBill'])->name('bills.create.form');
    
    // Workflow - Create Payment (for dual-role users)
    Route::get('patients/{patient}/payments/create', [RecordOfficerController::class, 'createPayment'])->name('payments.create.form');
    
    // Workflow - Submit for Vital Signs
    Route::get('patients/{patient}/vital-signs/request', [RecordOfficerController::class, 'requestForVitalSigns'])->name('vital-signs.request');
    
    
    // Export
    Route::get('patients/{patient}/export', [RecordOfficerController::class, 'exportRecord'])->name('patients.export');
});

// Accountant Routes - Billing and Payment Management
Route::middleware(['auth', 'verified', 'role:accountant'])->prefix('accountant')->name('accountant.')->group(function () {
    Route::get('/', [AccountantController::class, 'dashboard'])->name('dashboard');
    
    // Bills Management
    Route::get('bills', BillWorkspace::class)->name('bills.index');
    Route::get('/bills/create/', BillWorkspace::class)->name('bills.create');
    Route::get('bills/unpaid', BillWorkspace::class)->name('bills.unpaid');
    Route::get('bills/deleted', BillWorkspace::class)->name('bills.deleted');
    Route::get('/bills/patient-details', [AccountantController::class, 'patientDetailsByHospitalNumber'])->name('bills.patient-details');
    Route::get('/bills/walkin/create', [AccountantController::class, 'createWalkinBill'])->name('bills.create-walkin');
    Route::post('bills', [AccountantController::class, 'storeBill'])->name('bills.store');
    
    // bill paymnt routes
    Route::get('bills/payments/verify', [AccountantController::class, 'verifyBill'])->name('bills.payments.verify');
    Route::post('bills/payments/verify', [AccountantController::class, 'verifyBillNow'])->name('bills.payments.verify-now');
    Route::get('bills/{bill}/payments/create', [AccountantController::class, 'createPaymentForBill'])->name('bills.payments.create');
    Route::post('bills/{bill}/payments', [AccountantController::class, 'storePaymentForBill'])->name('bills.payments.store');
    Route::get('bills/{bill}', [AccountantController::class, 'showBill'])->name('bills.show');
    Route::get('bills/{bill}/edit', BillWorkspace::class)->name('bills.edit');
    Route::put('bills/{bill}', [AccountantController::class, 'updateBill'])->name('bills.update');
    Route::delete('bills/{bill}', [AccountantController::class, 'deleteBill'])->name('bills.delete');
    Route::put('bills/{bill}/restore', [AccountantController::class, 'restoreBill'])->withTrashed()->name('bills.restore');

    // Payments Management
    Route::get('payments', PaymentWorkspace::class)->name('payments.index');
    Route::get('{patient}/payments/create', [AccountantController::class, 'createPayment'])->name('payments.create');
    Route::post('payments', [AccountantController::class, 'storePayment'])->name('payments.store');
    Route::get('payments/{payment}/receipt', [AccountantController::class, 'paymentReceipt'])->name('payments.receipt');
    Route::get('patients/{patient}/payment-history', [AccountantController::class, 'patientPaymentHistory'])->name('patient-payment-history');
    
    // Insurance Billing
    Route::get('insurance-billing', [AccountantController::class, 'insuranceBilling'])->name('insurance-billing');
});

Route::middleware(['auth', 'verified', 'role:finance_officer'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('bills', BillManagement::class)->name('bills.index');
    Route::get('bills/{bill}', [BillController::class, 'show'])->name('bills.show');
    Route::get('payments', PaymentManagement::class)->name('payments.index');
    Route::get('payments/{payment}/receipt', [AdminPaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('expenses', ExpenseManagement::class)->name('expenses.index');
    Route::get('revenues', RevenueManagement::class)->name('revenues.index');
    Route::get('expenses/pdf', [FinanceReportDownloadController::class, 'expenses'])->name('expenses.pdf');
    Route::get('revenues/pdf', [FinanceReportDownloadController::class, 'revenues'])->name('revenues.pdf');
});

// nurse routes - Vital Signs Recording and Patient Monitoring
Route::middleware(['auth', 'verified', 'role:nurse'])->prefix('nurse')->name('nurse.')->group(function () {
    Route::get('/', [VitalSignsController::class, 'dashboard'])->name('dashboard');
    Route::get('patients/{patient}/vital-signs/submit', [RecordOfficerController::class, 'submitForVitalSigns'])->name('vital-signs.submit');
});

// Vital Signs Routes (for medical staff - Nurse/Doctor)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('vital-signs/patients/{patient}/create', [VitalSignsController::class, 'createForm'])->name('vital_signs.create');
    Route::post('vital-signs/patients/{patient}', [VitalSignsController::class, 'store'])->name('vital_signs.store');
    Route::get('vital-signs/patients/{patient}/history', [VitalSignsController::class, 'history'])->name('vital_signs.history');
});

require __DIR__.'/installation.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/nurse.php';
require __DIR__.'/midwife.php';
require __DIR__.'/lab.php';
require __DIR__.'/radiology.php';
require __DIR__.'/doctor.php';
require __DIR__.'/patient.php';
require __DIR__.'/pharmacy.php';
require __DIR__.'/department.php';
require __DIR__.'/medical-director.php';
require __DIR__.'/reports.php';

require __DIR__.'/specialist.php';

require __DIR__.'/partner-network.php';
