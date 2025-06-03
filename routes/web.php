<?php

use App\Http\Controllers\FinanceTracker\AIAssistantController;
use App\Http\Controllers\FinanceTracker\CategoryController;
use App\Http\Controllers\FinanceTracker\DashboardController;
use App\Http\Controllers\FinanceTracker\TransactionController;
use App\Http\Controllers\FinanceTracker\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('welcome');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
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

    // AI Assistant
    Route::get('/ai-assistant', [AIAssistantController::class, 'index'])->name('ai-assistant.index');
    Route::post('/ai-assistant/chat', [AIAssistantController::class, 'chat'])->name('ai-assistant.chat');
    Route::delete('/ai-assistant/chat-history', [AIAssistantController::class, 'deleteChatHistory'])->name('ai-assistant.delete-chat-history');
});
