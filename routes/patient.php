<?php
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Patient\VitalSignController;
use App\Http\Controllers\Patient\InvestigationController;
use App\Http\Controllers\Patient\AdmissionController;
use App\Http\Controllers\Patient\PrescriptionController;
use App\Http\Controllers\Patient\ObservationController;
use App\Http\Controllers\Patient\DrugChartController;
use App\Http\Controllers\Patient\FluidBalanceController;
use App\Http\Controllers\Patient\ContinuationController;
use App\Http\Controllers\Patient\DischargeController;
use App\Http\Controllers\Patient\VisitController;
use App\Livewire\Clinical\AdmissionWorkspace;
use App\Livewire\Clinical\ContinuationSheet;
use App\Livewire\Clinical\DischargeWorkspace;
use App\Livewire\Clinical\DrugChartWorkspace;
use App\Livewire\Clinical\FluidBalanceWorkspace;
use App\Livewire\Clinical\InvestigationRequestWorkspace;
use App\Livewire\Clinical\ObservationRecorder;
use App\Livewire\Clinical\PrescriptionWorkspace;
use App\Livewire\Clinical\VitalSignRecorder;
use Illuminate\Support\Facades\Route;

Route::name('patient.')
    ->middleware('auth')
    ->prefix('patient')
    ->group(function () {
        Route::get('/', [PatientController::class, 'index'])->name('index');
        Route::get('/{patient}/show', [PatientController::class, 'show'])->name('show');
        Route::get('/{patient}/history', [PatientController::class, 'history'])->name('history');
        Route::get('/search', [PatientController::class, 'search'])->name('search');
        // vital signs routes
        Route::name('vitalsign.')
        ->prefix('vital-sign')
        ->group(function () {
            Route::get('/{patient}/create', VitalSignRecorder::class)->name('create');
            Route::post('/{patient}/register', [VitalSignController::class, 'register'])->name('register');
            Route::get('/{vitalSign}/edit', [VitalSignController::class, 'edit'])->name('edit');
            Route::put('/{vitalSign}/update', [VitalSignController::class, 'update'])->name('update');
        });
        // investigation request routes
        Route::name('investigation.')
        ->prefix('investigation')
        ->group(function () {
            Route::get('/{patient}/create', InvestigationRequestWorkspace::class)->name('create');
            Route::post('/{patient}/store', [InvestigationController::class, 'store'])->name('store');
            Route::get('/{investigationRequest}/show', [InvestigationController::class, 'show'])->name('show');
        });

        // investigation request routes
        Route::name('visit.')
        ->prefix('visit')
        ->group(function () {
            Route::get('/{patient}/referred-to-nurse', [VisitController::class, 'referredToNurse'])->name('referred-to-nurse');
            Route::get('/{patient}/referred-to-doctor', [VisitController::class, 'referredToDoctor'])->name('referred-to-doctor');
        });

         Route::name('continuation.')
        ->prefix('continuation')
        ->group(function () {
            Route::get('/{patient}/create', ContinuationSheet::class)->name('create');
            Route::post('/{patient}/store', [ContinuationController::class, 'store'])->name('store');
        });

        // admission routes
        Route::name('admission.')
        ->prefix('admission')
        ->group(function () {
            Route::get('/{patient}/create', AdmissionWorkspace::class)->name('create');
            Route::get('/{admission}/confirmed', [AdmissionController::class, 'confirmed'])->name('confirmed');
            Route::post('/{patient}/store', [AdmissionController::class, 'store'])->name('store');
        });

        // admission routes
        Route::name('discharge.')
        ->prefix('discharge')
        ->group(function () {
            Route::get('/{admission}/create', DischargeWorkspace::class)->name('create');
            Route::post('/{admission}/store', [DischargeController::class, 'store'])->name('store');
        });

         // observation routes
        Route::name('observation.')
        ->prefix('observation')
        ->group(function () {
            Route::get('/{patient}/record', ObservationRecorder::class)->name('record');
            Route::post('/{patient}/register', [ObservationController::class, 'register'])->name('register');
        });

         // balance fluid routes
        Route::name('fluidbalance.')
        ->prefix('fluid-balance')
        ->group(function () {
            Route::get('/{patient}/record', FluidBalanceWorkspace::class)->name('record');
            Route::post('/{patient}/register', [FluidBalanceController::class, 'register'])->name('register');
        });

         // observation routes
        Route::name('drugchart.')
        ->prefix('drugchart')
        ->group(function () {
            Route::get('/{patient}/record', DrugChartWorkspace::class)->name('record');
            Route::post('/{patient}/register', [DrugchartController::class, 'register'])->name('register');
        });

        // prescription routes
        Route::name('prescription.')
        ->prefix('prescription')
        ->group(function () {
            Route::get('/{patient}/create', PrescriptionWorkspace::class)->name('create');
            Route::get('/{prescription}/show', [PrescriptionController::class, 'show'])->name('show');
            Route::get('/{prescription}/submit', [PrescriptionController::class, 'submit'])->name('submit');
            Route::post('/item/{prescriptionItem}/start', [PrescriptionController::class, 'startMedication'])->name('item.start');
            Route::post('/item/{prescriptionItem}/stop', [PrescriptionController::class, 'stopMedication'])->name('item.stop');
            Route::post('/{prescription}/add-medicine', [PrescriptionController::class, 'addMedicine'])->name('add');
            Route::delete('/{prescription}', [PrescriptionController::class, 'destroy'])->name('destroy');
            Route::post('/{patient}/store', [PrescriptionController::class, 'store'])->name('store');
        });
    });
