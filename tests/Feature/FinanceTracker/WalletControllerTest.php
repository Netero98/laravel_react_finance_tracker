<?php

use App\Models\User;
use App\Models\Wallet;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('warning when adding a wallet with unsupported currency', function () {
    $user = User::factory()->create();

    // Try to create a wallet with an unsupported currency
    $this->actingAs($user)
        ->post('/wallets', [
            'name' => 'Invalid Currency Wallet',
            'initial_balance' => 1000,
            'currency' => 'INVALID',
        ])
        ->assertSessionHasErrors(['currency']);

    // Verify no wallet was created
    expect(Wallet::count())->toBe(0);
});

test('wallet can be created with supported currency', function () {
    $user = User::factory()->create();

    // Get a list of supported currencies
    $supportedCurrencies = ExchangeRateService::ALL_EXTERNAL_CURRENCIES;

    // Try to create a wallet with a supported currency
    $this->actingAs($user)
        ->post('/wallets', [
            'name' => 'Valid Currency Wallet',
            'initial_balance' => 1000,
            'currency' => $supportedCurrencies[0], // Use the first supported currency
        ])
        ->assertRedirect();

    // Verify the wallet was created
    expect(Wallet::count())->toBe(1);
    expect(Wallet::first()->currency)->toBe($supportedCurrencies[0]);
});

test('wallet pagination works correctly', function () {
    $user = User::factory()->create();

    // Create 25 wallets (more than one page with default pagination)
    for ($i = 1; $i <= 25; $i++) {
        Wallet::create([
            'name' => "Wallet $i",
            'initial_balance' => 1000,
            'currency' => 'USD',
            'user_id' => $user->id,
        ]);
    }

    // Check first page
    $response = $this->actingAs($user)
        ->get('/wallets')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('finance-tracker/wallets/index')
            ->has('wallets.data')
            ->has('wallets.links')
        );

    // Verify pagination data is present through Inertia assertions
    $response->assertInertia(fn ($page) => $page
        ->component('finance-tracker/wallets/index')
        ->has('wallets.data', 8)
        ->has('wallets.meta')
        ->where('wallets.meta.current_page', 1)
        ->where('wallets.meta.last_page', fn ($lastPage) => $lastPage > 1)
    );

    // Check second page
    $response = $this->actingAs($user)
        ->get('/wallets?page=2')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('finance-tracker/wallets/index')
            ->has('wallets.data')
        );

    // Verify we're on page 2 through Inertia assertions
    $response->assertInertia(fn ($page) => $page
        ->component('finance-tracker/wallets/index')
        ->has('wallets.data')
        ->has('wallets.meta')
        ->where('wallets.meta.current_page', 2)
    );
});

test('wallet validation rejects negative initial balance', function () {
    $user = User::factory()->create();

    // Try to create a wallet with a negative initial balance
    $this->actingAs($user)
        ->post('/wallets', [
            'name' => 'Negative Balance Wallet',
            'initial_balance' => -1000,
            'currency' => 'USD',
        ])
        ->assertSessionHasErrors(['initial_balance']);

    // Verify no wallet was created
    expect(Wallet::count())->toBe(0);
});

test('wallet name must be unique for user', function () {
    $user = User::factory()->create();

    // Create a wallet
    Wallet::create([
        'name' => 'Duplicate Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    // Try to create another wallet with the same name
    $this->actingAs($user)
        ->post('/wallets', [
            'name' => 'Duplicate Wallet',
            'initial_balance' => 500,
            'currency' => 'EUR',
        ])
        ->assertSessionHasErrors(['name']);

    // Verify only one wallet was created
    expect(Wallet::count())->toBe(1);
});

test('different users can have wallets with the same name', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create a wallet for user1
    Wallet::create([
        'name' => 'Same Name Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user1->id,
    ]);

    // Create a wallet with the same name for user2
    $this->actingAs($user2)
        ->post('/wallets', [
            'name' => 'Same Name Wallet',
            'initial_balance' => 500,
            'currency' => 'EUR',
        ])
        ->assertRedirect();

    // Verify both wallets were created
    expect(Wallet::count())->toBe(2);
    expect(Wallet::where('user_id', $user1->id)->count())->toBe(1);
    expect(Wallet::where('user_id', $user2->id)->count())->toBe(1);
});
