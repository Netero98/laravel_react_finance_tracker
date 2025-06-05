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
            ->has('balanceHistoryUSD')
            ->has('currentBalanceUSD')
        );
});

test('finance dashboard shows correct balance history', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $incomeCategory = Category::create([
        'name' => 'Income Category',
        'user_id' => $user->id,
    ]);

    $expenseCategory = Category::create([
        'name' => 'Expense Category',
        'user_id' => $user->id,
    ]);

    // Create transactions on different dates
    $transaction1 = Transaction::create([
        'amount' => 500,
        'description' => 'Income Transaction',
        'date' => now()->subDays(5),
        'category_id' => $incomeCategory->id,
        'wallet_id' => $wallet->id,
    ]);

    $transaction2 = Transaction::create([
        'amount' => 200,
        'description' => 'Expense Transaction',
        'date' => now()->subDays(3),
        'category_id' => $expenseCategory->id,
        'wallet_id' => $wallet->id,
    ]);

    $transaction3 = Transaction::create([
        'amount' => 300,
        'description' => 'Another Income',
        'date' => now()->subDays(1),
        'category_id' => $incomeCategory->id,
        'wallet_id' => $wallet->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('balanceHistoryUSD', 4) // Should have 4 data points because of the initial balance
            ->has('currentBalanceUSD')
        );
});

test('finance dashboard shows correct current balance', function () {
    $user = User::factory()->create();

    // Create wallets with different currencies
    $usdWallet = Wallet::create([
        'name' => 'USD Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $eurWallet = Wallet::create([
        'name' => 'EUR Wallet',
        'initial_balance' => 500,
        'currency' => 'EUR',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('currentBalanceUSD', function ($balance) {
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
        'initial_balance' => 2000,
        'currency' => 'USD',
        'user_id' => $user2->id,
    ]);

    $category = Category::create([
        'name' => 'Other User Category',
        'user_id' => $user2->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 1000,
        'description' => 'Other User Transaction',
        'date' => now()->subDays(1),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    // User1 should not see user2's data
    $this->actingAs($user1)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('currentBalanceUSD', 0) // User1 has no wallets, so balance should be 0
            ->has('balanceHistoryUSD', 1) // Should have at least 1 entry (user creation date)
            ->where('balanceHistoryUSD', function ($balanceHistory) {
                if (count($balanceHistory) !== 1) {
                    return false;
                }

                $singleNullBalanceItem = $balanceHistory[0];

                //because there should be only one initial balance item without data
                if (!isset($singleNullBalanceItem['date']) || $singleNullBalanceItem['balance'] !== null) {
                    return false;
                }

                return true;
            })
        );
});

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertOk();
});

test('negative values are shown like zero in pie charts', function () {

});

test('current month income and expense include only current month transactions without transfers', function () {

});

