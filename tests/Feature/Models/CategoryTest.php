<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
//
test('system category is identified correctly', function () {
    $user = User::factory()->create();

    $regularCategory = Category::create([
        'name' => 'Regular Category',
        'user_id' => $user->id,
        'is_system' => false,
    ]);

    $systemCategory = Category::create([
        'name' => Category::SYSTEM_CATEGORY_TRANSFER,
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    expect($regularCategory->is_system)->toBeFalse();
    expect($systemCategory->is_system)->toBeTrue();
});

test('category can have transactions', function () {
    $user = User::factory()->create();

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

    $transaction1 = Transaction::create([
        'amount' => 100,
        'description' => 'Transaction 1',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    $transaction2 = Transaction::create([
        'amount' => 200,
        'description' => 'Transaction 2',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    expect($category->transactions)->toHaveCount(2);
    expect($category->transactions->pluck('id')->toArray())->toContain($transaction1->id, $transaction2->id);
});

test('system category constant is defined correctly', function () {
    expect(Category::SYSTEM_CATEGORY_TRANSFER)->toBe('Transfer');
});

test('category is cast correctly', function () {
    $user = User::factory()->create();

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    expect($category->is_system)->toBeTrue();
});
