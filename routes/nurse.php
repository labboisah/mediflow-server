<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Nurse\NurseController;
use App\Http\Controllers\Nurse\PatientController;
use App\Http\Controllers\Nurse\VitalSignsController;
use App\Http\Controllers\Patient\InvestigationController;
use App\Livewire\Clinical\ClinicalRecordIndex;
use App\Livewire\Nurse\PatientManagement;

Route::middleware(['auth', 'verified', 'role:nurse'])
->prefix('nurse')
->name('nurse.')
->group(function () {
    Route::get('/', [NurseController::class, 'dashboard'])->name('dashboard');
    Route::get('/clinicals/vital-signs', ClinicalRecordIndex::class)->defaults('type', 'vital-signs')->name('clinicals.vital-signs');
    Route::get('/clinicals/observations', ClinicalRecordIndex::class)->defaults('type', 'observations')->name('clinicals.observations');
    Route::get('/clinicals/drug-charts', ClinicalRecordIndex::class)->defaults('type', 'drug-charts')->name('clinicals.drug-charts');
    Route::get('/clinicals/fluid-balances', ClinicalRecordIndex::class)->defaults('type', 'fluid-balances')->name('clinicals.fluid-balances');
    Route::get('/clinicals/investigations', ClinicalRecordIndex::class)->defaults('type', 'investigations')->name('clinicals.investigations');
    Route::get('/admissions', [PatientController::class, 'admissions'])->name('admissions.index');
    Route::get('/admissions/{admission}/absconded', [PatientController::class, 'recordAbsconded'])->name('admissions.record-absconded');
    Route::get('/admissions/{admission}/sama', [PatientController::class, 'recordSAMA'])->name('admissions.record-sama');

    Route::name('patient.')
    ->prefix('patients')
    ->group(function () {
        Route::get('/', PatientManagement::class)->name('index');
        Route::get('/{patient}', [PatientController::class, 'show'])->name('show');
        Route::get('/{serviceRequest}/complete', [PatientController::class, 'complete'])->name('complete');
        Route::get('/{patientVisit}/close-visit', [PatientController::class, 'closeVisit'])->name('close-visit');
    });

    
});
