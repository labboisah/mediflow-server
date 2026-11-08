<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Department\ReportController;
use App\Livewire\Department\ConsumableManagement;
use App\Livewire\Department\ConsumableStockManagement;
use App\Livewire\Department\ConsumableUsageManagement;
use App\Livewire\Department\DepartmentUsers;
use App\Livewire\Department\InvestigationManagement;

Route::middleware(['auth', 'verified', 'role:head_of_department'])
->prefix('department')
->name('department.')
->group(function () {

    Route::name('consumables.')
    ->prefix('consumables')
    ->group(function () {
        Route::get('/', ConsumableManagement::class)->name('index');
    });

    Route::name('stocks.')
    ->prefix('consumable-stocks')
    ->group(function () {
        Route::get('/', ConsumableStockManagement::class)->name('index');
    });

    Route::get('/stock-usage', ConsumableUsageManagement::class)->name('stock-usage.index');
    Route::get('/users', DepartmentUsers::class)->name('users.index');
    Route::get('/investigations', InvestigationManagement::class)->name('investigations.index');

    Route::name('reports.')
    ->prefix('reports')
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::put('/pdf', [ReportController::class, 'pdf'])->name('pdf');
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
        
    });
});
