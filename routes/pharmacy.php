<?php
use App\Http\Controllers\Pharmacy\MedicineController;
use App\Http\Controllers\Pharmacy\StockController;
use App\Http\Controllers\Pharmacy\ExpiryController;
use App\Http\Controllers\Pharmacy\TransactionController;
use App\Http\Controllers\Pharmacy\PrescriptionController;
use App\Http\Controllers\Pharmacy\FinanceController;
use App\Livewire\Pharmacy\PrescriptionDispenseWorkspace;
use App\Livewire\Pharmacy\BatchManager;
use App\Livewire\Pharmacy\StockInventoryManager;
use App\Livewire\Pharmacy\StockReconciliationWorkspace;
use App\Livewire\Pharmacy\TransactionIndex;
use App\Livewire\Pharmacy\TransactionWorkspace;

Route::prefix('pharmacy')
->middleware(['auth', 'verified'])
->name('pharmacy.')
->group(function () {

Route::get('/services', \App\Livewire\Pharmacy\ServiceManager::class)->name('services.index');

Route::get('/receipts/{payment}', [FinanceController::class, 'receipt'])
    ->middleware('role:pharmacist,pharmacy_technician')->name('receipts.show');

Route::prefix('transactions')
->middleware('role:pharmacist,pharmacy_technician')
->name('transactions.')
->group(function () {
    Route::get('/', TransactionIndex::class)->name('index');
    Route::get('/create', TransactionWorkspace::class)->name('create');
    Route::post('/store', [TransactionController::class,'store'])->name('store');
    Route::get('/report', [TransactionController::class,'report'])->name('report');
});

Route::prefix('prescriptions')
->middleware('role:pharmacist,pharmacy_technician')
->name('prescriptions.')
->group(function () {
    Route::get('/', [PrescriptionController::class,'index'])->name('index');
    Route::get('/{prescription}', PrescriptionDispenseWorkspace::class)->name('show');
});

Route::prefix('finance')
->middleware('pharmacy.manager')
->name('finance.')
->group(function () {
    Route::get('/bills', [FinanceController::class,'bills'])->name('bills');
    Route::get('/payments', [FinanceController::class,'payments'])->name('payments');
    Route::get('/payments/{payment}/receipt', [FinanceController::class,'receipt'])->name('payments.receipt');
    Route::get('/report', [FinanceController::class,'report'])->name('report');
    Route::get('/report/download', [FinanceController::class,'downloadReport'])->name('report.download');
});

Route::middleware('pharmacy.manager')->group(function () {
    Route::prefix('medicines')
    ->name('medicines.')
    ->group(function () {
        Route::get('/', [MedicineController::class,'index'])->name('index');
        Route::get('/create', [MedicineController::class,'create'])->name('create');
        Route::post('/store', [MedicineController::class,'store'])->name('store');
    });

    Route::prefix('stocks')
    ->name('stocks.')
    ->group(function () {
        Route::get('/', StockInventoryManager::class)->name('index');
        Route::get('/reconciliation', StockReconciliationWorkspace::class)->middleware('role:head_of_department')->name('reconciliation');
        Route::get('/create', [StockController::class,'create'])->name('create');
        Route::post('/store', [StockController::class,'store'])->name('store');
    });

    Route::prefix('batches')
    ->name('batches.')
    ->group(function () {
        Route::get('/', BatchManager::class)->name('index');
    });

    Route::prefix('expiries')
    ->name('expiries.')
    ->group(function () {
        Route::get('/', [ExpiryController::class,'index'])->name('index');
    });
});


});
