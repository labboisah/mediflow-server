<?php

use App\Http\Controllers\InstallationActivationController;
use App\Http\Controllers\InstallationOnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/license', [InstallationActivationController::class, 'show'])->name('kernelbridge.license.show');
Route::post('/license', [InstallationActivationController::class, 'activate'])->middleware('throttle:5,1')->name('kernelbridge.license.activate');
Route::post('/license/verify', [InstallationActivationController::class, 'refresh'])->middleware(['auth', 'installation.admin', 'throttle:10,1'])->name('kernelbridge.license.verify');
Route::delete('/license', [InstallationActivationController::class, 'deactivate'])->middleware(['auth', 'installation.admin', 'throttle:5,1'])->name('kernelbridge.license.deactivate');
Route::get('/installation/setup', [InstallationOnboardingController::class, 'show'])->name('installation.setup');
Route::post('/installation/setup', [InstallationOnboardingController::class, 'store'])->middleware('throttle:5,1')->name('installation.setup.store');
