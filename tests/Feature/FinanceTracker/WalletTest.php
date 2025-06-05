<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access wallet endpoints', function () {
    $this->get('/wallets')->assertRedirect('/login');
    $this->post('/wallets')->assertRedirect('/login');
    $this->put('/wallets/1')->assertRedirect('/login');
    $this->delete('/wallets/1')->assertRedirect('/login');
});

test('users can view their wallets', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/wallets')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('finance-tracker/wallets/index')
            ->has('wallets.data', 1)
            ->where('wallets.data.0.name', 'Test Wallet')
        );
});

test('users can create a wallet', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/wallets', [
            'name' => 'New Wallet',
            'initial_balance' => 500,
            'currency' => 'EUR',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('wallets', [
        'name' => 'New Wallet',
        'initial_balance' => 500,
        'currency' => 'EUR',
        'user_id' => $user->id,
    ]);
});

test('users can update their wallet', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->put("/wallets/{$wallet->id}", [
            'name' => 'Updated Wallet',
            'initial_balance' => 1500,
            'currency' => 'USD',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'name' => 'Updated Wallet',
        'initial_balance' => 1500,
        'currency' => 'USD',
    ]);
});

test('users can delete their wallet', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete("/wallets/{$wallet->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('wallets', [
        'id' => $wallet->id,
    ]);
});

test('users cannot access wallets of other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Other User Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user2->id,
    ]);

    $this->actingAs($user1)
        ->get('/wallets')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('finance-tracker/wallets/index')
            ->has('wallets.data', 0)
        );

    $this->actingAs($user1)
        ->put("/wallets/{$wallet->id}", [
            'name' => 'Hacked Wallet',
            'initial_balance' => 0,
            'currency' => 'USD',
        ])
        ->assertForbidden();

    $this->actingAs($user1)
        ->delete("/wallets/{$wallet->id}")
        ->assertForbidden();
});

test('wallet validation rules are enforced', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/wallets', [
            'name' => '',
            'initial_balance' => 'not-a-number',
            'currency' => 'INVALID',
        ])
        ->assertSessionHasErrors(['name', 'initial_balance', 'currency']);

    $this->actingAs($user)
        ->post('/wallets', [
            'name' => 'Valid Wallet',
            'initial_balance' => -100, // Negative balance
            'currency' => 'USD',
        ])
        ->assertSessionHasErrors(['initial_balance']);
});


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
