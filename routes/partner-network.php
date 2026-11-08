<?php

use App\Http\Controllers\PartnerNetwork\DocumentController;
use App\Http\Controllers\PartnerNetwork\NetworkController;
use App\Http\Controllers\PartnerNetwork\PortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'verified'])->prefix('care-network')->name('network.')->group(function () {
    Route::get('/', [NetworkController::class, 'index'])->name('index');
    Route::post('/partners', [NetworkController::class, 'store'])->name('store');
    Route::post('/types', [NetworkController::class, 'types'])->name('types');
    Route::get('/partners/{id}', [NetworkController::class, 'show'])->name('show');
    Route::post('/partners/{id}/{action}', [NetworkController::class, 'action'])->name('action');
    Route::get('/consultations/{consultation}/dispatch', [NetworkController::class, 'createCase'])->name('dispatch.create');
    Route::post('/consultations/{consultation}/dispatch', [NetworkController::class, 'dispatch'])->name('dispatch');
    Route::get('/cases', [NetworkController::class, 'cases'])->name('cases');
    Route::get('/cases/{id}', [NetworkController::class, 'case'])->name('case');
    Route::post('/cases/{id}/{action}', [NetworkController::class, 'caseAction'])->name('case.action');
    Route::get('/reports', [NetworkController::class, 'reports'])->name('reports');
    Route::post('/documents/owner/{id}', [DocumentController::class, 'upload'])->name('documents.upload');
    Route::get('/documents/{id}', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('/documents/{id}/release', [DocumentController::class, 'release'])->name('documents.release');
});
Route::prefix('partner-portal')->name('partner.')->group(function () {
    Route::get('/login', [PortalController::class, 'loginForm'])->name('login');
    Route::post('/login', [PortalController::class, 'login'])->middleware('throttle:6,1')->name('login.submit');
    Route::get('/invitation/{token}', [PortalController::class, 'invitation'])->middleware('throttle:20,1')->name('invitation');
    Route::post('/invitation/{token}', [PortalController::class, 'redeem'])->middleware('throttle:6,1')->name('redeem');
    Route::middleware('auth:partner')->group(function () {
        Route::post('/logout', [PortalController::class, 'logout'])->name('logout');
        Route::get('/', [PortalController::class, 'index'])->name('index');
        Route::get('/onboarding/{id}', [PortalController::class, 'show'])->name('onboarding');
        Route::post('/onboarding/{id}/{action}', [PortalController::class, 'action'])->name('action');
        Route::get('/cases/{id}', [PortalController::class, 'case'])->name('case');
        Route::post('/cases/{id}/{action}', [PortalController::class, 'caseAction'])->name('case.action');
        Route::post('/documents/owner/{id}', [DocumentController::class, 'upload'])->name('documents.upload');
        Route::get('/documents/{id}', [DocumentController::class, 'download'])->name('documents.download');
    });
});
