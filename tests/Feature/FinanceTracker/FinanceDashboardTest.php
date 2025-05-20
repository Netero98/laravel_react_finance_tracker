<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access the finance dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can access the finance dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('balanceHistory')
            ->has('currentBalance')
        );
});

test('finance dashboard shows correct balance history', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $incomeCategory = Category::create([
        'name' => 'Income Category',
        'type' => 'income',
        'user_id' => $user->id,
    ]);

    $expenseCategory = Category::create([
        'name' => 'Expense Category',
        'type' => 'expense',
        'user_id' => $user->id,
    ]);

    // Create transactions on different dates
    $transaction1 = Transaction::create([
        'amount' => 500,
        'description' => 'Income Transaction',
        'date' => now()->subDays(5),
        'type' => 'income',
        'category_id' => $incomeCategory->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
    ]);

    $transaction2 = Transaction::create([
        'amount' => 200,
        'description' => 'Expense Transaction',
        'date' => now()->subDays(3),
        'type' => 'expense',
        'category_id' => $expenseCategory->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
    ]);

    $transaction3 = Transaction::create([
        'amount' => 300,
        'description' => 'Another Income',
        'date' => now()->subDays(1),
        'type' => 'income',
        'category_id' => $incomeCategory->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('balanceHistory', 3) // Should have 3 data points
            ->has('currentBalance')
        );
});

test('finance dashboard shows correct current balance', function () {
    $user = User::factory()->create();

    // Create wallets with different currencies
    $usdWallet = Wallet::create([
        'name' => 'USD Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $eurWallet = Wallet::create([
        'name' => 'EUR Wallet',
        'balance' => 500,
        'currency' => 'EUR',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('currentBalance', function ($balance) {
                // The balance should be approximately 1000 + (500 / 0.92) = ~1543.48
                // But we'll allow some flexibility due to exchange rate variations
                return $balance >= 1500 && $balance <= 1600;
            })
        );
});

test('finance dashboard only shows data for the authenticated user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create wallet and transactions for user2
    $wallet = Wallet::create([
        'name' => 'Other User Wallet',
        'balance' => 2000,
        'currency' => 'USD',
        'user_id' => $user2->id,
    ]);

    $category = Category::create([
        'name' => 'Other User Category',
        'type' => 'income',
        'user_id' => $user2->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 1000,
        'description' => 'Other User Transaction',
        'date' => now()->subDays(1),
        'type' => 'income',
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user2->id,
    ]);

    // User1 should not see user2's data
    $this->actingAs($user1)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('currentBalance', 0) // User1 has no wallets, so balance should be 0
            ->where('balanceHistory', []) // User1 has no transactions, so history should be empty
        );
});
