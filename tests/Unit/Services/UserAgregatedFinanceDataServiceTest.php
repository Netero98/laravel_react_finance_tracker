<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ExchangeRateService;
use App\Services\UserAgregatedFinanceDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user and authenticate
    $this->user = User::factory()->create();
    Auth::login($this->user);

    // Mock the ExchangeRateService
    $this->exchangeRateService = Mockery::mock(ExchangeRateService::class);
    $this->exchangeRateService->shouldReceive('getExchangeRates')
        ->andReturn([
            'USD' => 1,
            'EUR' => 0.85,
            'GBP' => 0.75,
        ]);

    $this->service = new UserAgregatedFinanceDataService($this->exchangeRateService);
});

test('getAllUserAgregatedFinanceData returns correct data structure', function () {
    // Create wallets
    $usdWallet = Wallet::create([
        'name' => 'USD Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $this->user->id,
    ]);

    $eurWallet = Wallet::create([
        'name' => 'EUR Wallet',
        'initial_balance' => 500,
        'currency' => 'EUR',
        'user_id' => $this->user->id,
    ]);

    // Create categories
    $incomeCategory = Category::create([
        'name' => 'Income',
        'user_id' => $this->user->id,
    ]);

    $expenseCategory = Category::create([
        'name' => 'Expense',
        'user_id' => $this->user->id,
    ]);

    $transferCategory = Category::create([
        'name' => Category::SYSTEM_CATEGORY_TRANSFER,
        'user_id' => $this->user->id,
        'is_system' => true,
    ]);

    // Create transactions
    Transaction::create([
        'amount' => 200,
        'description' => 'Income Transaction',
        'date' => now()->subDays(5),
        'category_id' => $incomeCategory->id,
        'wallet_id' => $usdWallet->id,
    ]);

    Transaction::create([
        'amount' => -100,
        'description' => 'Expense Transaction',
        'date' => now()->subDays(3),
        'category_id' => $expenseCategory->id,
        'wallet_id' => $usdWallet->id,
    ]);

    Transaction::create([
        'amount' => -50,
        'description' => 'Transfer Out',
        'date' => now()->subDays(2),
        'category_id' => $transferCategory->id,
        'wallet_id' => $usdWallet->id,
    ]);

    Transaction::create([
        'amount' => 50,
        'description' => 'Transfer In',
        'date' => now()->subDays(2),
        'category_id' => $transferCategory->id,
        'wallet_id' => $eurWallet->id,
    ]);

    // Get aggregated data
    $data = $this->service->getAllUserAgregatedFinanceData();

    // Test data structure
    expect($data->balanceHistoryUSD)->toBeArray();
    expect($data->currentBalanceUSD)->toBeNumeric();
    expect($data->walletData)->toBeArray();
    expect($data->currentMonthExpensesUSD)->toBeArray();
    expect($data->currentMonthIncomeUSD)->toBeArray();

    // Test specific values
    expect($data->currentBalanceUSD)->toBeGreaterThan(0);
    expect(count($data->walletData))->toBe(2);

    // Verify transfer transactions are excluded from income/expense calculations
    $expenseCategories = collect($data->currentMonthExpensesUSD)->pluck('name')->toArray();
    expect($expenseCategories)->toContain('Expense');
    expect($expenseCategories)->not()->toContain(Category::SYSTEM_CATEGORY_TRANSFER);

    $incomeCategories = collect($data->currentMonthIncomeUSD)->pluck('name')->toArray();
    expect($incomeCategories)->toContain('Income');
    expect($incomeCategories)->not()->toContain(Category::SYSTEM_CATEGORY_TRANSFER);
});

test('getAllUserAgregatedFinanceData handles currency conversion correctly', function () {
    // Create wallets with different currencies
    $usdWallet = Wallet::create([
        'name' => 'USD Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $this->user->id,
    ]);

    $eurWallet = Wallet::create([
        'name' => 'EUR Wallet',
        'initial_balance' => 850, // 850 EUR = 1000 USD at 0.85 rate
        'currency' => 'EUR',
        'user_id' => $this->user->id,
    ]);

    // Get aggregated data
    $data = $this->service->getAllUserAgregatedFinanceData();

    // Test currency conversion
    // 1000 USD + (850 EUR / 0.85) = 1000 + 1000 = 2000 USD
    expect($data->currentBalanceUSD)->toBeGreaterThanOrEqual(1999);
    expect($data->currentBalanceUSD)->toBeLessThanOrEqual(2001);
});

test('getAllUserAgregatedFinanceData only includes data for authenticated user', function () {
    // Create wallets for authenticated user
    $userWallet = Wallet::create([
        'name' => 'User Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $this->user->id,
    ]);

    // Create another user and wallet
    $otherUser = User::factory()->create();
    $otherWallet = Wallet::create([
        'name' => 'Other Wallet',
        'initial_balance' => 2000,
        'currency' => 'USD',
        'user_id' => $otherUser->id,
    ]);

    // Get aggregated data
    $data = $this->service->getAllUserAgregatedFinanceData();

    // Test that only authenticated user's data is included
    expect(count($data->walletData))->toBe(1);
    expect($data->walletData[0]['name'])->toBe('User Wallet');
    expect($data->currentBalanceUSD)->toBe(1000);
});
