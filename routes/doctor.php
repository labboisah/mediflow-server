<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Doctor\DoctorController;
use App\Http\Controllers\Doctor\PatientController;
use App\Livewire\Clinical\ClinicalRecordIndex;
use App\Livewire\Doctor\PatientManagement;


Route::middleware(['auth', 'verified', 'role:doctor'])
->prefix('doctor')
->name('doctor.')
->group(function () {
   
    Route::name('patient.')
    ->prefix('patients')
    ->group(function () {
        Route::get('/', PatientManagement::class)->name('index');
        Route::get('/{patient}/show', [PatientController::class, 'show'])->name('show');
        Route::get('/{serviceRequest}/complete', [PatientController::class, 'complete'])->name('complete');
        Route::get('/{patientVisit}/close-visit', [PatientController::class, 'closeVisit'])->name('close-visit');        
    });

  
    Route::get('/clinicals/vital-signs', ClinicalRecordIndex::class)->defaults('type', 'vital-signs')->name('clinicals.vital-signs');
    Route::get('/clinicals/observations', ClinicalRecordIndex::class)->defaults('type', 'observations')->name('clinicals.observations');
    Route::get('/clinicals/investigations', ClinicalRecordIndex::class)->defaults('type', 'investigations')->name('clinicals.investigations');
    Route::get('/clinicals/admissions', ClinicalRecordIndex::class)->defaults('type', 'admissions')->name('clinicals.admissions');
    Route::get('/clinicals/prescriptions', ClinicalRecordIndex::class)->defaults('type', 'prescriptions')->name('clinicals.prescriptions');
    Route::get('/clinicals/continuations', ClinicalRecordIndex::class)->defaults('type', 'continuations')->name('clinicals.continuations');
    Route::get('/clinicals/drug-charts', ClinicalRecordIndex::class)->defaults('type', 'drug-charts')->name('clinicals.drug-charts');
    Route::get('/clinicals/fluid-balances', ClinicalRecordIndex::class)->defaults('type', 'fluid-balances')->name('clinicals.fluid-balances');
});
