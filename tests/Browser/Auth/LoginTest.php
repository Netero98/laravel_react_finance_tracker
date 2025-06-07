<?php

use Laravel\Dusk\Browser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can view login page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
                ->assertSee('Log in to your account')
                ->assertSee('Email address')
                ->assertSee('Password')
                ->assertSee('Remember me');
    });
})->group('auth');

test('user can login with correct credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
                ->type('email', 'test@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');
    });
})->group('auth');

test('user cannot login with incorrect email', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
                ->type('email', 'wrong@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->assertSee('These credentials do not match our records');
    });
})->group('auth');

test('user cannot login with incorrect password', function () {
    $user = User::factory()->create([
        'email' => 'test2@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
                ->type('email', 'test2@example.com')
                ->type('password', 'wrong-password')
                ->press('Log in')
                ->assertSee('These credentials do not match our records');
    });
})->group('auth');

test('user is redirected to intended page after login', function () {
    $user = User::factory()->create([
        'email' => 'test3@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/dashboard')  // This requires authentication
                ->assertPathIs('/login')  // Should be redirected to login
                ->type('email', 'test3@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');  // Should be redirected to the intended page
    });
})->group('auth');
