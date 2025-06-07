<?php

use Laravel\Dusk\Browser;

test('basic example', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
//            ->waitForRoute('login') // Ждём, пока маршрут станет 'login'
            ->waitForText('Log in to your account') // Ждём, пока появится нужный текст
            ->assertRouteIs('login');
    });
});
