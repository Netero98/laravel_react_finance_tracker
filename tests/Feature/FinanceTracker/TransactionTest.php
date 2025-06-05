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
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/transactions', [
            'amount' => 100,
            'description' => 'New Transaction',
            'date' => now()->format('Y-m-d'),
            'category_id' => $category->id,
            'wallet_id' => $wallet->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('transactions', [
        'amount' => 100,
        'description' => 'New Transaction',
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    // Check that wallet balance was updated
    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'initial_balance' => 1000,
    ]);
});

test('users can create an income transaction', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Income Category',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/transactions', [
            'amount' => 200,
            'description' => 'Income Transaction',
            'date' => now()->format('Y-m-d'),
            'category_id' => $category->id,
            'wallet_id' => $wallet->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('transactions', [
        'amount' => 200,
        'description' => 'Income Transaction',
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    // Check that wallet balance was updated
    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'initial_balance' => 1000,
    ]);
});

test('users can update their transaction', function () {
    $user = User::factory()->create();

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

    $this->actingAs($user)
        ->put("/transactions/{$transaction->id}", [
            'amount' => 50, // Changed from 100 to 50
            'description' => 'Updated Transaction',
            'date' => now()->format('Y-m-d'),
            'category_id' => $category->id,
            'wallet_id' => $wallet->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'amount' => 50,
        'description' => 'Updated Transaction',
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'initial_balance' => 1000, //the same as before permutations
    ]);
});

test('users can delete their transaction', function () {
    $user = User::factory()->create();

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

    $this->actingAs($user)
        ->delete("/transactions/{$transaction->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('transactions', [
        'id' => $transaction->id,
    ]);

    // Check that wallet balance was restored
    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'initial_balance' => 1000, //the same as before
    ]);
});

test('users cannot access transactions of other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Other User Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user2->id,
    ]);

    $category = Category::create([
        'name' => 'Other User Category',
        'user_id' => $user2->id,
    ]);

    $transaction = Transaction::create([
        'amount' => 100,
        'description' => 'Other User Transaction',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
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
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->post('/transactions', [
            'amount' => 'not-a-number',
            'description' => str_repeat('a', 300), // Too long
            'date' => 'invalid-date',
            'category_id' => 999, // Non-existent
            'wallet_id' => 999, // Non-existent
        ])
        ->assertSessionHasErrors(['amount', 'date', 'category_id', 'wallet_id']);
});

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

test('transactions are sorted DESC by datetime, not only date', function () {

});
