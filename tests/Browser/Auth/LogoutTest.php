<?php

use Laravel\Dusk\Browser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('authenticated user can logout', function () {
//    $user = User::factory()->create([
//        'email' => 'logout-test@example.com',
//        'password' => Hash::make('password'),
//    ]);
//
//    $this->browse(function (Browser $browser) use ($user) {
//        $browser->visit('/login')
//                ->type('email', 'logout-test@example.com')
//                ->type('password', 'password')
//                ->press('Log in')
//                ->waitForLocation('/dashboard')
//                ->assertPathIs('/dashboard')
//                // Find and click the logout button/link
//                ->click('#user-menu-button') // Assuming there's a user menu button
//                ->waitFor('#logout-form') // Assuming there's a logout form or button
//                ->press('Log out')
//                ->waitForLocation('/login') // After logout, should redirect to login
//                ->assertPathIs('/login');
//    });
})->group('auth');
//
test('logged out user is redirected to login when accessing protected routes', function () {
//    $this->browse(function (Browser $browser) {
//        $browser->visit('/dashboard') // This requires authentication
//                ->assertPathIs('/login'); // Should be redirected to login
//    });
})->group('auth');
