<?php

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
        'balance' => 1000,
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
            'balance' => 500,
            'currency' => 'EUR',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('wallets', [
        'name' => 'New Wallet',
        'balance' => 500,
        'currency' => 'EUR',
        'user_id' => $user->id,
    ]);
});

test('users can update their wallet', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->put("/wallets/{$wallet->id}", [
            'name' => 'Updated Wallet',
            'balance' => 1500,
            'currency' => 'USD',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'name' => 'Updated Wallet',
        'balance' => 1500,
        'currency' => 'USD',
    ]);
});

test('users can delete their wallet', function () {
    $user = User::factory()->create();

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'balance' => 1000,
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
        'balance' => 1000,
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
            'balance' => 0,
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
            'balance' => 'not-a-number',
            'currency' => 'INVALID',
        ])
        ->assertSessionHasErrors(['name', 'balance', 'currency']);

    $this->actingAs($user)
        ->post('/wallets', [
            'name' => 'Valid Wallet',
            'balance' => -100, // Negative balance
            'currency' => 'USD',
        ])
        ->assertSessionHasErrors(['balance']);
});
