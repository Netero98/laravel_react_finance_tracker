<?php

use Laravel\Dusk\Browser;
use App\Models\User;

test('user can view registration page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
                ->assertSee('Register')
                ->assertSee('Email')
                ->assertSee('Password')
                ->assertSee('Confirm Password');
    });
})->group('auth');

test('user can register with valid credentials', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
                ->type('name', 'Test User')
                ->type('email', 'test_' . time() . '@example.com')
                ->type('password', 'password')
                ->type('password_confirmation', 'password')
                ->press('Register')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');
    });
})->group('auth');

test('user cannot register with invalid email', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
                ->type('name', 'Test User')
                ->type('email', 'invalid-email')
                ->type('password', 'password')
                ->type('password_confirmation', 'password')
                ->press('Register')
                ->assertSee('The email must be a valid email address');
    });
})->group('auth');

test('user cannot register with password confirmation mismatch', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
                ->type('name', 'Test User')
                ->type('email', 'test_' . time() . '@example.com')
                ->type('password', 'password')
                ->type('password_confirmation', 'different-password')
                ->press('Register')
                ->assertSee('The password confirmation does not match');
    });
})->group('auth');
