<?php

use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Category;

test('getInitialBalancePlusTransactionsDelta returns initial balance when no transactions', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    expect($wallet->getInitialBalancePlusTransactionsDelta())->toBe(1000);
});

test('getInitialBalancePlusTransactionsDelta returns correct balance with transactions', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    // Create categories
    $incomeCategory = Category::create([
        'name' => 'Income Category',
        'user_id' => $user->id,
        'is_system' => false,
    ]);

    $expenseCategory = Category::create([
        'name' => 'Expense Category',
        'user_id' => $user->id,
        'is_system' => false,
    ]);

    // Create income transaction
    Transaction::create([
        'amount' => 500,
        'description' => 'Income',
        'date' => now(),
        'category_id' => $incomeCategory->id,
        'wallet_id' => $wallet->id,
    ]);

    // Create expense transaction
    Transaction::create([
        'amount' => -200,
        'description' => 'Expense',
        'date' => now(),
        'category_id' => $expenseCategory->id,
        'wallet_id' => $wallet->id,
    ]);

    // Expected balance: 1000 (initial) + 500 (income) - 200 (expense) = 1300
    expect($wallet->getInitialBalancePlusTransactionsDelta())->toBe(1300);
});

test('getInitialBalancePlusTransactionsDelta uses runtime cache', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    // Create a category
    $category = Category::create([
        'name' => 'Income Category',
        'user_id' => $user->id,
        'is_system' => false,
    ]);

    // Create a transaction
    Transaction::create([
        'amount' => 500,
        'description' => 'Income',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    // First call should calculate and cache
    $firstResult = $wallet->getInitialBalancePlusTransactionsDelta();

    // Create another transaction that won't be included in the cached result
    Transaction::create([
        'amount' => 200,
        'description' => 'More Income',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    // Second call should use cached value
    $secondResult = $wallet->getInitialBalancePlusTransactionsDelta();

    // Both results should be the same due to caching
    expect($firstResult)->toBe(1500);
    expect($secondResult)->toBe($firstResult);

    // Refresh the model to clear the cache
    $wallet->refresh();

    // Now it should recalculate with both transactions
    expect($wallet->getInitialBalancePlusTransactionsDelta())->toBe(1700);
});
