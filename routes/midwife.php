<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Midwife\MidwifeController;
use App\Http\Controllers\AntenatalCareController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\LabourController;
use App\Http\Controllers\LabourProgressController;
use App\Http\Controllers\NewbornController;
use App\Http\Controllers\NewbornExaminationController;
use App\Http\Controllers\PostnatalExaminationController;
use App\Http\Controllers\ChildFollowUpController;
use App\Http\Controllers\MaternalMedicationController;
use App\Http\Controllers\Midwife\PatientController;
use App\Livewire\Midwife\AntenatalManagement;
use App\Livewire\Midwife\ChildFollowUpManagement;
use App\Livewire\Midwife\DeliveryManagement;
use App\Livewire\Midwife\LabourManagement;
use App\Livewire\Midwife\NewbornManagement;
use App\Livewire\Midwife\PostnatalManagement;

Route::middleware(['auth', 'verified', 'role:midwife,administrator'])
    ->prefix('midwife')
    ->name('midwife.')
    ->group(function () {
        
        // Dashboard route
        Route::get('/', [MidwifeController::class, 'dashboard'])->name('dashboard');
        Route::get('/anc-management/{patient?}', AntenatalManagement::class)->name('anc-management');
        Route::get('/labour-management/{patient?}', LabourManagement::class)->name('labour-management');
        Route::get('/delivery-management/{patient?}', DeliveryManagement::class)->name('delivery-management');
        Route::get('/newborn-management/{patient?}', NewbornManagement::class)->name('newborn-management');
        Route::get('/postnatal-management/{patient?}', PostnatalManagement::class)->name('postnatal-management');
        Route::get('/child-follow-up-management/{patient?}', ChildFollowUpManagement::class)->name('child-follow-up-management');
        
        Route::name('patient.')
            ->prefix('patient')
            ->group(function () {
            
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('/{patient}', [PatientController::class, 'show'])->name('show');
            Route::get('/{serviceRequest}/complete', [PatientController::class, 'complete'])->name('complete');
            Route::get('/{patientVisit}/close-visit', [PatientController::class, 'closeVisit'])->name('close-visit');
            Route::get('/{patient}/progress', [MidwifeController::class, 'progress'])->name('progress');
        });
        
        
        // Antenatal Care routes
        Route::name('antenatal.')
            ->prefix('antenatal-care')
            ->group(function () {
                // List all female patients with antenatal care
                Route::get('/', [AntenatalCareController::class, 'index'])->name('index');
                
                // Create new antenatal care record
                Route::get('/patient/{patient}/create', [AntenatalCareController::class, 'create'])->name('create');
                Route::post('/patient/{patient}/store', [AntenatalCareController::class, 'store'])->name('store');
                
                // View antenatal care record
                Route::get('/{antenatalCare}/show', [AntenatalCareController::class, 'show'])->name('show');
                
                // Edit antenatal care record
                Route::get('/{antenatalCare}/edit', [AntenatalCareController::class, 'edit'])->name('edit');
                Route::put('/{antenatalCare}/update', [AntenatalCareController::class, 'update'])->name('update');
                
                // Delete antenatal care record
                Route::delete('/{antenatalCare}/delete', [AntenatalCareController::class, 'destroy'])->name('destroy');
                
                // View all antenatal records for a patient
                Route::get('/patient/{patient}/records', [AntenatalCareController::class, 'patientRecords'])->name('patient-records');
            });

        // Labour Management routes
        Route::name('labour.')
            ->prefix('labour')
            ->group(function () {
                // List all female patients with labour records
                Route::get('/', [LabourController::class, 'index'])->name('index');
                
                // Create new labour record
                Route::get('/patient/{patient}/create', [LabourController::class, 'create'])->name('create');
                Route::post('/patient/{patient}/store', [LabourController::class, 'store'])->name('store');
                
                // View labour record
                Route::get('/{labour}/show', [LabourController::class, 'show'])->name('show');
                
                // Edit labour record
                Route::get('/{labour}/edit', [LabourController::class, 'edit'])->name('edit');
                Route::put('/{labour}/update', [LabourController::class, 'update'])->name('update');
                
                // Delete labour record
                Route::delete('/{labour}/delete', [LabourController::class, 'destroy'])->name('destroy');
                
                // View all labour records for a patient
                Route::get('/patient/{patient}/records', [LabourController::class, 'patientRecords'])->name('patient-records');

                // Labour progress routes
                Route::name('progress.')
                    ->prefix('/{labour}/progress')
                    ->group(function () {
                        Route::get('/', [LabourProgressController::class, 'index'])->name('index');
                        Route::get('/create', [LabourProgressController::class, 'create'])->name('create');
                        Route::post('/store', [LabourProgressController::class, 'store'])->name('store');
                        Route::get('/{labourProgress}/show', [LabourProgressController::class, 'show'])->name('show');
                        Route::get('/{labourProgress}/edit', [LabourProgressController::class, 'edit'])->name('edit');
                        Route::put('/{labourProgress}/update', [LabourProgressController::class, 'update'])->name('update');
                        Route::delete('/{labourProgress}/delete', [LabourProgressController::class, 'destroy'])->name('destroy');
                    });
            });

        // Delivery routes
        Route::name('delivery.')
            ->prefix('delivery')
            ->group(function () {
                Route::get('/', [DeliveryController::class, 'index'])->name('index');
                Route::get('/labour/{labour}/create', [DeliveryController::class, 'create'])->name('create');
                Route::post('/labour/{labour}/store', [DeliveryController::class, 'store'])->name('store');
                Route::get('/{delivery}/show', [DeliveryController::class, 'show'])->name('show');
                Route::get('/{delivery}/edit', [DeliveryController::class, 'edit'])->name('edit');
                Route::put('/{delivery}/update', [DeliveryController::class, 'update'])->name('update');
                Route::delete('/{delivery}/delete', [DeliveryController::class, 'destroy'])->name('destroy');
                Route::get('/patient/{patient}/records', [DeliveryController::class, 'patientRecords'])->name('patient-records');
            });

        // Newborn routes
        Route::name('newborn.')
            ->prefix('newborn')
            ->group(function () {
                Route::get('/deliveries', [NewbornController::class, 'index'])->name('index');
                Route::get('/delivery/{delivery}/create', [NewbornController::class, 'create'])->name('create');
                Route::post('/delivery/{delivery}/store', [NewbornController::class, 'store'])->name('store');
                Route::get('/{newborn}/show', [NewbornController::class, 'show'])->name('show');
                Route::get('/{newborn}/edit', [NewbornController::class, 'edit'])->name('edit');
                Route::put('/{newborn}/update', [NewbornController::class, 'update'])->name('update');
                Route::delete('/{newborn}/delete', [NewbornController::class, 'destroy'])->name('destroy');
                Route::get('/patient/{patient}/records', [NewbornController::class, 'patientRecords'])->name('patient-records');
            });

        // Newborn Examination routes
        Route::name('newborn-examination.')
            ->prefix('newborn-examination')
            ->group(function () {
                Route::get('/newborns', [NewbornExaminationController::class, 'index'])->name('index');
                Route::get('/newborn/{newborn}/record', [NewbornExaminationController::class, 'record'])->name('record');
                Route::get('/newborn/{newborn}/create', [NewbornExaminationController::class, 'create'])->name('create');
                Route::post('/newborn/{newborn}/store', [NewbornExaminationController::class, 'store'])->name('store');
                Route::get('/{newbornExamination}/show', [NewbornExaminationController::class, 'show'])->name('show');
                Route::get('/{newbornExamination}/edit/', [NewbornExaminationController::class, 'edit'])->name('edit');
                Route::put('/{newbornExamination}/update', [NewbornExaminationController::class, 'update'])->name('update');
                Route::delete('/{newbornExamination}/delete', [NewbornExaminationController::class, 'destroy'])->name('destroy');
            });

        // Postnatal Examination routes
        Route::name('postnatal-examination.')
            ->prefix('postnatal-examination')
            ->group(function () {
                Route::get('/deliveries', [PostnatalExaminationController::class, 'index'])->name('index');
                Route::get('/delivery/{delivery}/record', [PostnatalExaminationController::class, 'record'])->name('record');
                Route::get('/delivery/{delivery}/create', [PostnatalExaminationController::class, 'create'])->name('create');
                Route::post('/delivery/{delivery}/store', [PostnatalExaminationController::class, 'store'])->name('store');
                Route::get('/{postnatalExamination}/show', [PostnatalExaminationController::class, 'show'])->name('show');
                Route::get('/{postnatalExamination}/edit', [PostnatalExaminationController::class, 'edit'])->name('edit');
                Route::put('/{postnatalExamination}/update', [PostnatalExaminationController::class, 'update'])->name('update');
                Route::delete('/{postnatalExamination}/delete', [PostnatalExaminationController::class, 'destroy'])->name('destroy');
            });

        // Child Follow-up routes
        Route::name('child-follow-up.')
            ->prefix('child-follow-up')
            ->group(function () {
                Route::get('/newborns', [ChildFollowUpController::class, 'index'])->name('index');
                Route::get('/newborn/{newborn}/record', [ChildFollowUpController::class, 'record'])->name('record');
                Route::get('/newborn/{newborn}/create', [ChildFollowUpController::class, 'create'])->name('create');
                Route::post('/newborn/{newborn}/store', [ChildFollowUpController::class, 'store'])->name('store');
                Route::get('/{childFollowUp}/show', [ChildFollowUpController::class, 'show'])->name('show');
                Route::get('/{childFollowUp}/edit', [ChildFollowUpController::class, 'edit'])->name('edit');
                Route::put('/{childFollowUp}/update', [ChildFollowUpController::class, 'update'])->name('update');
                Route::delete('/{childFollowUp}/delete', [ChildFollowUpController::class, 'destroy'])->name('destroy');
            });

            // medications routes
            Route::name('medications.')
                ->prefix('medications')
                ->group(function () {
                    Route::get('/{patient}', [MaternalMedicationController::class, 'index'])->name('index');
                    Route::get('/patient/{patient}/create', [MaternalMedicationController::class, 'create'])->name('create');
                    Route::post('/patient/{patient}/store', [MaternalMedicationController::class, 'store'])->name('store');
                    Route::get('/{medication}/show', [MaternalMedicationController::class, 'show'])->name('show');
                    Route::get('/{medication}/edit', [MaternalMedicationController::class, 'edit'])->name('edit');
                    Route::put('/{medication}/update', [MaternalMedicationController::class, 'update'])->name('update');
                    Route::delete('/{medication}/delete', [MaternalMedicationController::class, 'destroy'])->name('destroy');
                });
    });
