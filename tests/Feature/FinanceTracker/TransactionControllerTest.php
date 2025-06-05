<?php

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;

test('error when creating a transaction without a category', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    // Try to create a transaction without a category
    $this->actingAs($user)
        ->post('/transactions', [
            'amount' => 100,
            'description' => 'No Category Transaction',
            'date' => now()->format('Y-m-d'),
            'wallet_id' => $wallet->id,
            // Missing category_id
        ])
        ->assertSessionHasErrors(['category_id']);

    // Verify no transaction was created
    expect(Transaction::count())->toBe(0);
});

test('transfer transactions create two transactions', function () {
    $user = User::factory()->create();

    // Create wallets
    $fromWallet = Wallet::create([
        'name' => 'From Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $toWallet = Wallet::create([
        'name' => 'To Wallet',
        'initial_balance' => 500,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    // Create transfer category
    $transferCategory = Category::create([
        'name' => Category::SYSTEM_CATEGORY_TRANSFER,
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    // Create a transfer transaction
    $response = $this->actingAs($user)
        ->post('/transactions/transfer', [
            'from_amount' => 200,
            'to_amount' => 200,
            'description' => 'Transfer Transaction',
            'date' => now()->format('Y-m-d'),
            'category_id' => $transferCategory->id,
            'from_wallet_id' => $fromWallet->id,
            'to_wallet_id' => $toWallet->id,
        ]);

    // Verify two transactions were created
    expect(Transaction::count())->toBe(2);

    // Verify the first transaction is a negative amount (money out)
    $outTransaction = Transaction::where('wallet_id', $fromWallet->id)->first();
    expect($outTransaction)->not()->toBeNull();
    expect($outTransaction->amount)->toEqual((float) -200);
    expect($outTransaction->category_id)->toBe($transferCategory->id);

    // Verify the second transaction is a positive amount (money in)
    $inTransaction = Transaction::where('wallet_id', $toWallet->id)->first();
    expect($inTransaction)->not()->toBeNull();
    expect($inTransaction->amount)->toBeGreaterThan(0);
    expect($inTransaction->category_id)->toBe($transferCategory->id);

    // Verify the amounts match (one is negative of the other)
    expect(abs((float)$outTransaction->amount))->toEqual((float)$inTransaction->amount);
});

test('cannot specify the same wallet for from and to in a transfer', function () {
    $user = User::factory()->create();

    // Create wallet
    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    // Create transfer category
    $transferCategory = Category::create([
        'name' => Category::SYSTEM_CATEGORY_TRANSFER,
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    // Try to create a transfer with the same wallet for from and to
    $this->actingAs($user)
        ->post('/transactions/transfer', [
            'from_amount' => 200,
            'to_amount' => 200,
            'description' => 'Invalid Transfer',
            'date' => now()->format('Y-m-d'),
            'category_id' => $transferCategory->id,
            'from_wallet_id' => $wallet->id,
            'to_wallet_id' => $wallet->id,
        ])
        ->assertSessionHasErrors();

    // Verify no transactions were created
    expect(Transaction::count())->toBe(0);
});

test('currency conversion for transfers between wallets with different currencies', function () {
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

    // Create transfer category
    $transferCategory = Category::create([
        'name' => Category::SYSTEM_CATEGORY_TRANSFER,
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    // Create a transfer transaction with auto currency conversion
    $this->actingAs($user)
        ->post('/transactions/transfer', [
            'from_amount' => 100,
            'to_amount' => 85, // test that minus doesnt matter
            'description' => 'Currency Transfer',
            'date' => now()->format('Y-m-d'),
            'category_id' => $transferCategory->id,
            'from_wallet_id' => $usdWallet->id,
            'to_wallet_id' => $eurWallet->id,
        ]);

    // Verify two transactions were created
    expect(Transaction::count())->toBe(2);

    // Verify the first transaction is a negative amount in USD
    $outTransaction = Transaction::where('wallet_id', $usdWallet->id)->first();
    expect($outTransaction)->not()->toBeNull();
    expect((float)$outTransaction->amount)->toEqual(-100.00);

    $inTransaction = Transaction::where('wallet_id', $eurWallet->id)->first();
    expect($inTransaction)->not()->toBeNull();
    expect((float)$inTransaction->amount)->toEqual(85.00);
});

test('transaction type is tied to category', function () {
    $user = User::factory()->create();

    // Create wallet
    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    // Create income category
    $incomeCategory = Category::create([
        'name' => 'Income',
        'user_id' => $user->id,
    ]);

    // Create expense category
    $expenseCategory = Category::create([
        'name' => 'Expense',
        'user_id' => $user->id,
    ]);

    // Create an income transaction
    $this->actingAs($user)
        ->post('/transactions', [
            'amount' => 100, // Positive amount
            'description' => 'Income Transaction',
            'date' => now()->format('Y-m-d'),
            'category_id' => $incomeCategory->id,
            'wallet_id' => $wallet->id,
        ]);

    // Create an expense transaction
    $this->actingAs($user)
        ->post('/transactions', [
            'amount' => -50,
            'description' => 'Expense Transaction',
            'date' => now()->format('Y-m-d'),
            'category_id' => $expenseCategory->id,
            'wallet_id' => $wallet->id,
        ]);

    // Verify the transactions were created with the correct amounts based on category type
    $incomeTransaction = Transaction::where(Transaction::PROP_DESCRIPTION, 'Income Transaction')->first();
    expect($incomeTransaction)->not()->toBeNull();
    expect((float)$incomeTransaction->amount)->toEqual(100.00);

    $expenseTransaction = Transaction::where('description', 'Expense Transaction')->first();
    expect($expenseTransaction)->not()->toBeNull();
    expect((float)$expenseTransaction->amount)->toEqual(-50.00);
});

test('transaction can be created with a date in the past', function () {
    $user = User::factory()->create();
});

test('transaction can be created with a date in the future', function () {
    $user = User::factory()->create();
});

test('transfer transaction store endpoint return error if ordinary transaction was tried to be stored', function () {

});

test('ordinary transaction store endpoint return error if transfer transaction was sent to be stored', function () {

});
