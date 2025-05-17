<?php

use App\Http\Controllers\Todo\TodoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::prefix('todos')->name('todos.')->group(function () {
        Route::get('/', [TodoController::class, 'index'])->name('index');
        Route::post('/', [TodoController::class, 'store'])->name('store');
        Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
        Route::patch('/{todo}/toggle-completed', [TodoController::class, 'toggleCompleted'])->name('toggle-completed');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
