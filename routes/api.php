<?php

use App\Http\Controllers\Api\FhirDiagnosticReportController;
use App\Http\Controllers\Api\FhirEncounterController;
use App\Http\Controllers\Api\FhirMedicationController;
use App\Http\Controllers\Api\FhirMedicationDispenseController;
use App\Http\Controllers\Api\FhirMedicationRequestController;
use App\Http\Controllers\Api\FhirObservationController;
use App\Http\Controllers\Api\FhirPatientController;
use App\Http\Controllers\Api\FhirServiceRequestController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Middleware\EnsureFhirReadAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('fhir/R4')->group(function () {
    Route::get('/metadata', [FhirPatientController::class, 'metadata'])->name('fhir.metadata');

    Route::middleware([EnsureFhirReadAccess::class.':Patient', 'throttle:60,1'])->group(function () {
        Route::get('/Patient', [FhirPatientController::class, 'search'])->name('fhir.patient.search');
        Route::get('/Patient/{id}', [FhirPatientController::class, 'read'])
            ->where('id', '[A-Za-z0-9.-]{1,64}')
            ->name('fhir.patient.read');
    });

    Route::middleware([EnsureFhirReadAccess::class.':Encounter', 'throttle:60,1'])->group(function () {
        Route::get('/Encounter', [FhirEncounterController::class, 'search'])->name('fhir.encounter.search');
        Route::get('/Encounter/{id}', [FhirEncounterController::class, 'read'])
            ->where('id', '[A-Za-z0-9.-]{1,64}')
            ->name('fhir.encounter.read');
    });

    Route::middleware([EnsureFhirReadAccess::class.':Observation', 'throttle:60,1'])->group(function () {
        Route::get('/Observation', [FhirObservationController::class, 'search'])->name('fhir.observation.search');
        Route::get('/Observation/{id}', [FhirObservationController::class, 'read'])
            ->where('id', '[A-Za-z0-9.-]{1,64}')
            ->name('fhir.observation.read');
    });

    Route::middleware([EnsureFhirReadAccess::class.':ServiceRequest', 'throttle:60,1'])->group(function () {
        Route::get('/ServiceRequest', [FhirServiceRequestController::class, 'search'])->name('fhir.servicerequest.search');
        Route::get('/ServiceRequest/{id}', [FhirServiceRequestController::class, 'read'])
            ->where('id', '[A-Za-z0-9.-]{1,64}')
            ->name('fhir.servicerequest.read');
    });

    Route::middleware([EnsureFhirReadAccess::class.':DiagnosticReport', 'throttle:60,1'])->group(function () {
        Route::get('/DiagnosticReport', [FhirDiagnosticReportController::class, 'search'])->name('fhir.diagnosticreport.search');
        Route::get('/DiagnosticReport/{id}', [FhirDiagnosticReportController::class, 'read'])
            ->where('id', '[A-Za-z0-9.-]{1,64}')
            ->name('fhir.diagnosticreport.read');
    });

    Route::middleware([EnsureFhirReadAccess::class.':Medication', 'throttle:60,1'])->group(function () {
        Route::get('/Medication', [FhirMedicationController::class, 'search'])->name('fhir.medication.search');
        Route::get('/Medication/{id}', [FhirMedicationController::class, 'read'])
            ->where('id', '[A-Za-z0-9.-]{1,64}')
            ->name('fhir.medication.read');
    });

    Route::middleware([EnsureFhirReadAccess::class.':MedicationRequest', 'throttle:60,1'])->group(function () {
        Route::get('/MedicationRequest', [FhirMedicationRequestController::class, 'search'])->name('fhir.medicationrequest.search');
        Route::get('/MedicationRequest/{id}', [FhirMedicationRequestController::class, 'read'])
            ->where('id', '[A-Za-z0-9.-]{1,64}')
            ->name('fhir.medicationrequest.read');
    });

    Route::middleware([EnsureFhirReadAccess::class.':MedicationDispense', 'throttle:60,1'])->group(function () {
        Route::get('/MedicationDispense', [FhirMedicationDispenseController::class, 'search'])->name('fhir.medicationdispense.search');
        Route::get('/MedicationDispense/{id}', [FhirMedicationDispenseController::class, 'read'])
            ->where('id', '[A-Za-z0-9.-]{1,64}')
            ->name('fhir.medicationdispense.read');
    });
});

/*
|--------------------------------------------------------------------------
| Sync Routes
|--------------------------------------------------------------------------
|
| Bidirectional sync between local hospital server and online platform
|
*/
Route::prefix('v1/sync')->middleware('auth.sync.token')->group(function () {
    // Receive sync records from remote server
    Route::post('/records', [SyncController::class, 'receiveSyncRecords']);
    Route::post('/batch', [SyncController::class, 'receiveBatchSync']);

    // Retrieve pending syncs (for pull-based sync if needed)
    Route::get('/pending', [SyncController::class, 'getPendingSyncs']);
    Route::get('/status/{syncUuid}', [SyncController::class, 'getSyncStatus']);

    // Health check
    Route::get('/health', [SyncController::class, 'healthCheck']);
});

Route::middleware('auth.sync.token')->get('/v1/health', [SyncController::class, 'healthCheck']);
