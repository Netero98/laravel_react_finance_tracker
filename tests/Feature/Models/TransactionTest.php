<?php

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('transaction belongs to wallet', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 100,
        'description' => 'Test Transaction',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    expect($transaction->wallet->id)->toBe($wallet->id);
    expect($transaction->wallet->name)->toBe('Test Wallet');
});

test('transaction belongs to category', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 100,
        'description' => 'Test Transaction',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    expect($transaction->category->id)->toBe($category->id);
    expect($transaction->category->name)->toBe('Test Category');
});

test('transaction amount is cast to decimal', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 100.50,
        'description' => 'Test Transaction',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    expect($transaction->amount)->toEqual(100.50);
    expect($transaction->amount)->toBeNumeric();
    expect($transaction->getAttributes()[Transaction::PROP_AMOUNT])->toBeNumeric();
});

test('transaction date is cast to datetime', function () {
    $date = now();

    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 100,
        'description' => 'Test Transaction',
        'date' => $date,
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    expect($transaction->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($transaction->date->toDateString())->toBe($date->toDateString());
});
