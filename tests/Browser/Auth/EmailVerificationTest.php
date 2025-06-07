<?php

use Laravel\Dusk\Browser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

test('unverified user sees verification notice', function () {
    $user = User::factory()->create([
        'email' => 'unverified@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => null,
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
                ->type('email', 'unverified@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->waitForLocation('/verify-email')
                ->assertPathIs('/verify-email')
                ->assertSee('Verify Email Address')
                ->assertSee('A new verification link has been sent');
    });
})->group('auth');

test('verified user is not shown verification notice', function () {
    $user = User::factory()->create([
        'email' => 'verified@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
                ->type('email', 'verified@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard')
                ->visit('/verify-email')  // Try to visit verification page directly
                ->assertPathIsNot('/verify-email');  // Should be redirected away
    });
})->group('auth');

test('user can request a new verification link', function () {
    $user = User::factory()->create([
        'email' => 'need-verification@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => null,
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
                ->type('email', 'need-verification@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->waitForLocation('/verify-email')
                ->assertPathIs('/verify-email')
                ->press('Resend Verification Email')
                ->waitForText('A new verification link has been sent')
                ->assertSee('A new verification link has been sent');
    });
})->group('auth');

test('user can verify email with valid verification link', function () {
    Event::fake([Verified::class]);

    $user = User::factory()->create([
        'email' => 'to-verify@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => null,
    ]);

    // Generate a valid verification URL
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->browse(function (Browser $browser) use ($user, $verificationUrl) {
        $browser->visit('/login')
                ->type('email', 'to-verify@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->waitForLocation('/verify-email')
                ->visit($verificationUrl)
                ->waitForLocation('/dashboard')
                ->assertPathIs('/dashboard');
    });

    Event::assertDispatched(Verified::class);
})->group('auth');
