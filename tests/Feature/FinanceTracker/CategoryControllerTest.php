<?php

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('users cannot create duplicate categories by name', function () {
    $user = User::factory()->create();

    // Create a category
    Category::create([
        'name' => 'Duplicate Category',
        'user_id' => $user->id,
    ]);

    // Try to create another category with the same name
    $this->actingAs($user)
        ->post('/categories', [
            'name' => 'Duplicate Category',
        ])
        ->assertSessionHasErrors(['name']);

    // Verify only one category with that name exists
    expect(Category::where('name', 'Duplicate Category')->count())->toBe(1);
});

test('users cannot modify system categories', function () {
    $user = User::factory()->create();

    // Create a system category
    $systemCategory = Category::create([
        'name' => Category::SYSTEM_CATEGORY_TRANSFER,
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    // Try to update the system category
    $this->actingAs($user)
        ->put("/categories/{$systemCategory->id}", [
            'name' => 'Modified System Category',
        ])
        ->assertSessionHasErrors();

    // Verify the category wasn't modified
    $systemCategory->refresh();
    expect($systemCategory->name)->toBe(Category::SYSTEM_CATEGORY_TRANSFER);
});

test('users cannot delete system categories', function () {
    $user = User::factory()->create();

    // Create a system category
    $systemCategory = Category::create([
        'name' => Category::SYSTEM_CATEGORY_TRANSFER,
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    // Try to delete the system category
    $this->actingAs($user)
        ->delete("/categories/{$systemCategory->id}")
        ->assertSessionHasErrors();

    // Verify the category wasn't deleted
    expect(Category::find($systemCategory->id))->not()->toBeNull();
});

test('system category transfer is always at the top of the list', function () {
    $user = User::factory()->create();

    // Create several categories in alphabetical order
    Category::create([
        'name' => 'A Category',
        'user_id' => $user->id,
    ]);

    Category::create([
        'name' => 'B Category',
        'user_id' => $user->id,
    ]);

    // Create the Transfer category last (so it would normally be last in creation order)
    $transferCategory = Category::create([
        'name' => Category::SYSTEM_CATEGORY_TRANSFER,
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    // Get the categories list and verify the order
    $this->actingAs($user)
        ->get('/categories')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('finance-tracker/categories/index')
            ->has('categories.data')
            ->where('categories.data.0.name', Category::SYSTEM_CATEGORY_TRANSFER)
            ->where('categories.data.0.id', $transferCategory->id)
        );
});

test('transfer system category is created for new users during registration', function () {
    // Register a new user
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Get the newly created user
    $user = User::where('email', 'test@example.com')->first();

    // Verify the Transfer category was created
    $transferCategory = Category::where('user_id', $user->id)
        ->where('name', Category::SYSTEM_CATEGORY_TRANSFER)
        ->where('is_system', true)
        ->first();

    expect($transferCategory)->not()->toBeNull();
});
