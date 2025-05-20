<?php

use App\Http\Controllers\FinanceTracker\CategoryController;
use App\Http\Controllers\FinanceTracker\DashboardController;
use App\Http\Controllers\FinanceTracker\TransactionController;
use App\Http\Controllers\FinanceTracker\WalletController;
use App\Http\Controllers\Todo\TodoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->intended(route('dashboard', absolute: false));
})->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

//    Route::prefix('todos')->name('todos.')->group(function () {
//        Route::get('/', [TodoController::class, 'index'])->name('index');
//        Route::post('/', [TodoController::class, 'store'])->name('store');
//        Route::patch('/{todo}', [TodoController::class, 'update'])->name('update');
//        Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
//        Route::patch('/{todo}/toggle-completed', [TodoController::class, 'toggleCompleted'])->name('toggle-completed');
//    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Finance routes
Route::middleware(['auth'])->group(function () {
    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Wallets
    Route::get('/wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::post('/wallets', [WalletController::class, 'store'])->name('wallets.store');
    Route::put('/wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
    Route::delete('/wallets/{wallet}', [WalletController::class, 'destroy'])->name('wallets.destroy');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
});
