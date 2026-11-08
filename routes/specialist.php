<?php

use App\Http\Controllers\Specialist\SetupController;
use App\Http\Controllers\Specialist\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('specialist')->name('specialist.')->group(function () {
    Route::get('/', [WorkspaceController::class, 'index'])->name('index');
    Route::get('/setup', [SetupController::class, 'index'])->name('setup');
    Route::post('/setup/{type}', [SetupController::class, 'save'])->name('setup.save');
    Route::get('/patients/{patient}/history', [WorkspaceController::class, 'history'])->name('patient-history');
    Route::post('/patients', [WorkspaceController::class, 'register'])->name('patients.store');
    Route::post('/patient-access/revoke', [WorkspaceController::class, 'revoke'])->name('patients.revoke');
    Route::post('/patient-access', [WorkspaceController::class, 'assign'])->name('patients.assign');
    Route::post('/appointments', [WorkspaceController::class, 'book'])->name('appointments.store');
    Route::get('/reports', [WorkspaceController::class, 'reports'])->name('reports');
    Route::post('/referrals/{referral}/respond', [WorkspaceController::class, 'respond'])->name('referrals.respond');
    Route::get('/documents/{document}', [WorkspaceController::class, 'document'])->name('documents.show');
    Route::get('/consultations/{consultation}/billing', [WorkspaceController::class, 'billing'])->name('billing');
    Route::get('/consultations/{consultation}', [WorkspaceController::class, 'show'])->name('consultations.show');
    Route::post('/consultations/{consultation}/documents', [WorkspaceController::class, 'upload'])->name('documents.store');
    Route::post('/consultations/{consultation}/{action}', [WorkspaceController::class, 'action'])->name('consultations.action');
    Route::get('/consultations/{consultation}/print/{kind}/{record}', [WorkspaceController::class, 'print'])->name('print');
});

Route::get('/clinical-history/specialist/{patient}', [WorkspaceController::class, 'archive'])->middleware(['auth', 'verified'])->name('clinical-history.specialist');
