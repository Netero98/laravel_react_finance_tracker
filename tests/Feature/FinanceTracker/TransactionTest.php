<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access transaction endpoints', function () {
    $this->get('/transactions')->assertRedirect('/login');
    $this->post('/transactions')->assertRedirect('/login');
    $this->put('/transactions/1')->assertRedirect('/login');
    $this->delete('/transactions/1')->assertRedirect('/login');
});

test('users can view their transactions', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'type' => 'expense',
        'user_id' => $user->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 100,
        'description' => 'Test Transaction',
        'date' => now(),
        'type' => 'expense',
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/transactions')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('finance-tracker/transactions/index')
            ->has('transactions.data', 1)
            ->has('categories')
            ->has('wallets')
            ->where('transactions.data.0.description', 'Test Transaction')
        );
});

test('users can create a transaction', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'type' => 'expense',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/transactions', [
            'amount' => 100,
            'description' => 'New Transaction',
            'date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'category_id' => $category->id,
            'wallet_id' => $wallet->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('transactions', [
        'amount' => 100,
        'description' => 'New Transaction',
        'type' => 'expense',
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
    ]);

    // Check that wallet balance was updated
    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'balance' => 900, // 1000 - 100
    ]);
});

test('users can create an income transaction', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Income Category',
        'type' => 'income',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/transactions', [
            'amount' => 200,
            'description' => 'Income Transaction',
            'date' => now()->format('Y-m-d'),
            'type' => 'income',
            'category_id' => $category->id,
            'wallet_id' => $wallet->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('transactions', [
        'amount' => 200,
        'description' => 'Income Transaction',
        'type' => 'income',
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
    ]);

    // Check that wallet balance was updated
    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'balance' => 1200, // 1000 + 200
    ]);
});

test('users can update their transaction', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'type' => 'expense',
        'user_id' => $user->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 100,
        'description' => 'Test Transaction',
        'date' => now(),
        'type' => 'expense',
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
    ]);

    // Update wallet balance to reflect the transaction
    $wallet->balance -= 100;
    $wallet->save();

    $this->actingAs($user)
        ->put("/transactions/{$transaction->id}", [
            'amount' => 50, // Changed from 100 to 50
            'description' => 'Updated Transaction',
            'date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'category_id' => $category->id,
            'wallet_id' => $wallet->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'amount' => 50,
        'description' => 'Updated Transaction',
    ]);

    // Check that wallet balance was updated (100 added back, 50 deducted)
    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'balance' => 950, // 900 + 100 - 50
    ]);
});

test('users can delete their transaction', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'type' => 'expense',
        'user_id' => $user->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 100,
        'description' => 'Test Transaction',
        'date' => now(),
        'type' => 'expense',
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
    ]);

    // Update wallet balance to reflect the transaction
    $wallet->balance -= 100;
    $wallet->save();

    $this->actingAs($user)
        ->delete("/transactions/{$transaction->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('transactions', [
        'id' => $transaction->id,
    ]);

    // Check that wallet balance was restored
    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'balance' => 1000, // 900 + 100
    ]);
});

test('users cannot access transactions of other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Other User Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user2->id,
    ]);

    $category = Category::create([
        'name' => 'Other User Category',
        'type' => 'expense',
        'user_id' => $user2->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 100,
        'description' => 'Other User Transaction',
        'date' => now(),
        'type' => 'expense',
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
        'user_id' => $user2->id,
    ]);

    $this->actingAs($user1)
        ->get('/transactions')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('finance-tracker/transactions/index')
            ->has('transactions.data', 0)
        );

    $this->actingAs($user1)
        ->put("/transactions/{$transaction->id}", [
            'amount' => 50,
            'description' => 'Hacked Transaction',
            'date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'category_id' => $category->id,
            'wallet_id' => $wallet->id,
        ])
        ->assertForbidden();

    $this->actingAs($user1)
        ->delete("/transactions/{$transaction->id}")
        ->assertForbidden();
});

test('transaction validation rules are enforced', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'type' => 'expense',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/transactions', [
            'amount' => 'not-a-number',
            'description' => str_repeat('a', 300), // Too long
            'date' => 'invalid-date',
            'type' => 'invalid-type',
            'category_id' => 999, // Non-existent
            'wallet_id' => 999, // Non-existent
        ])
        ->assertSessionHasErrors(['amount', 'date', 'type', 'category_id', 'wallet_id']);
});
