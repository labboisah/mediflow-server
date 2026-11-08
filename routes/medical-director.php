<?php

use App\Http\Controllers\Admin\BedController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\InvestigationController;
use App\Http\Controllers\Admin\PatientRegisterController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\WardController;
use App\Http\Controllers\MedicalDirector\AdmissionManagementController;
use App\Http\Controllers\MedicalDirector\StatisticsReportController;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ExpenseManagement;
use App\Livewire\Admin\FileTypeManagement;
use App\Livewire\Admin\RevenueManagement;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:medical_director'])
    ->prefix('medical-director')
    ->name('medical-director.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('index');

        Route::get('patient-register', [PatientRegisterController::class, 'index'])->name('patient-register.index');
        Route::get('patient-register/csv', [PatientRegisterController::class, 'csv'])->name('patient-register.csv');
        Route::get('patient-register/pdf', [PatientRegisterController::class, 'pdf'])->name('patient-register.pdf');
        Route::get('patient-register/{patient}/summary', [PatientRegisterController::class, 'summary'])->name('patient-register.summary');

        Route::get('statistics', [StatisticsReportController::class, 'index'])->name('statistics.index');
        Route::get('statistics/pdf', [StatisticsReportController::class, 'pdf'])->name('statistics.pdf');
        Route::get('admissions', [AdmissionManagementController::class, 'index'])->name('admissions.index');
        Route::post('admissions/{admission}/discharge', [AdmissionManagementController::class, 'discharge'])->name('admissions.discharge');
        Route::post('admissions/{admission}/sama', [AdmissionManagementController::class, 'recordSama'])->name('admissions.sama');

        Route::resource('departments', DepartmentController::class);
        Route::resource('wards', WardController::class);
        Route::resource('investigations', InvestigationController::class);
        Route::resource('services', ServiceController::class);
        Route::put('services/{service}/restore', [ServiceController::class, 'restore'])->name('services.restore');
        Route::get('file-types', FileTypeManagement::class)->name('file-types.index');

        Route::prefix('wards/beds')->name('beds.')->group(function () {
            Route::get('/{ward}', [BedController::class, 'index'])->name('index');
            Route::get('/{ward}/create', [BedController::class, 'create'])->name('create');
            Route::post('/{ward}/store', [BedController::class, 'store'])->name('store');
            Route::get('/{bed}/edit', [BedController::class, 'edit'])->name('edit');
            Route::put('/{bed}/update', [BedController::class, 'update'])->name('update');
            Route::delete('/{bed}/destroy', [BedController::class, 'destroy'])->name('destroy');
        });

        Route::get('expenses', ExpenseManagement::class)->name('expenses.index');
        Route::get('revenues', RevenueManagement::class)->name('revenues.index');
    });
