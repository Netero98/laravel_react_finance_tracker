<?php

use Laravel\Dusk\Browser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;

test('user can view forgot password page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
                ->clickLink('Forgot your password?')
                ->assertPathIs('/forgot-password')
                ->assertSee('Email')
                ->assertSee('Email Password Reset Link');
    });
})->group('auth');

test('user can request password reset link', function () {
    $user = User::factory()->create([
        'email' => 'reset-test@example.com',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/forgot-password')
                ->type('email', 'reset-test@example.com')
                ->press('Email Password Reset Link')
                ->waitForText('We have emailed your password reset link')
                ->assertSee('We have emailed your password reset link');
    });
})->group('auth');

test('user cannot request password reset for non-existent email', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/forgot-password')
                ->type('email', 'nonexistent@example.com')
                ->press('Email Password Reset Link')
                ->waitForText('We have emailed your password reset link')
                ->assertSee('We have emailed your password reset link');
        // Note: For security reasons, Laravel shows the same message even if the email doesn't exist
    });
})->group('auth');

test('user can reset password with valid token', function () {
    // Create a user
    $user = User::factory()->create([
        'email' => 'reset-token-test@example.com',
        'password' => Hash::make('old-password'),
    ]);

    // Create a password reset token
    $token = Password::createToken($user);

    $this->browse(function (Browser $browser) use ($user, $token) {
        $browser->visit('/reset-password/' . $token)
                ->type('email', 'reset-token-test@example.com')
                ->type('password', 'new-password')
                ->type('password_confirmation', 'new-password')
                ->press('Reset Password')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');

        // Try logging in with the new password
        $browser->visit('/login')
                ->type('email', 'reset-token-test@example.com')
                ->type('password', 'new-password')
                ->press('Log in')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');
    });
})->group('auth');

test('user cannot reset password with invalid token', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/reset-password/invalid-token')
                ->type('email', 'test@example.com')
                ->type('password', 'new-password')
                ->type('password_confirmation', 'new-password')
                ->press('Reset Password')
                ->assertSee('This password reset token is invalid');
    });
})->group('auth');
