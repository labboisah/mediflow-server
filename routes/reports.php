<?php

use App\Http\Controllers\Report\FinancialReportController;
use App\Http\Controllers\Report\ActivityReportController;
use App\Http\Controllers\Report\MyActivityReportController;
use App\Http\Controllers\Report\PaymentReportController;
use App\Livewire\Reports\ActivityReport;
use App\Livewire\Reports\FinanceReport;
use App\Livewire\Reports\MyActivityReport;
use App\Livewire\Reports\PaymentReport;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:administrator,medical_director,doctor,nurse,midwife,record,accountant,finance_officer,lab_scientist,lab_technician,radiologist,radiographer,pharmacist,head_of_department'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/my-activities', MyActivityReport::class)->name('my-activities.index');
    Route::get('/my-activities/pdf', [MyActivityReportController::class, 'pdf'])->name('my-activities.pdf');
});

// Financial Reports Routes (for accountant, administrator, and medical director roles only)
Route::middleware(['auth', 'verified', 'role:administrator,medical_director,accountant,finance_officer'])->prefix('reports')->name('reports.')->group(function () {
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', FinanceReport::class)->name('index');
        Route::get('/search', [FinancialReportController::class, 'search'])->name('search');
        Route::get('/export', [FinancialReportController::class, 'export'])->name('export');
        Route::get('/pdf', [FinancialReportController::class, 'pdf'])->name('pdf');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', PaymentReport::class)->name('index');
        Route::get('/export', [PaymentReportController::class, 'export'])->name('export');
        Route::get('/pdf', [PaymentReportController::class, 'pdf'])->name('pdf');
    });

    Route::middleware('role:administrator')->prefix('activities/{department}')->name('activities.')->group(function () {
        Route::get('/', ActivityReport::class)->name('show');
        Route::get('/export', [ActivityReportController::class, 'export'])->name('export');
        Route::get('/pdf', [ActivityReportController::class, 'pdf'])->name('pdf');
    });
});
