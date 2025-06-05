<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Category;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access category endpoints', function () {
    $this->get('/categories')->assertRedirect('/login');
    $this->post('/categories')->assertRedirect('/login');
    $this->put('/categories/1')->assertRedirect('/login');
    $this->delete('/categories/1')->assertRedirect('/login');
});

test('users can view their categories', function () {
    $user = User::factory()->create();

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/categories')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('finance-tracker/categories/index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Test Category')
        );
});

test('users can create a category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/categories', [
            'name' => 'New Category',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('categories', [
        'name' => 'New Category',
        'user_id' => $user->id,
    ]);
});

test('users can update their category', function () {
    $user = User::factory()->create();

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->put("/categories/{$category->id}", [
            'name' => 'Updated Category',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Category',
    ]);
});

test('users can delete their category', function () {
    $user = User::factory()->create();

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Test Category',
    ]);

    $this->actingAs($user)
        ->delete("/categories/{$category->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('users cannot access categories of other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $category = Category::create([
        'name' => 'Other User Category',
        'user_id' => $user2->id,
    ]);

    $this->actingAs($user1)
        ->get('/categories')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('finance-tracker/categories/index')
            ->has('categories.data', 0)
        );

    $this->actingAs($user1)
        ->put("/categories/{$category->id}", [
            'name' => 'Hacked Category',
        ])
        ->assertForbidden();

    $this->actingAs($user1)
        ->delete("/categories/{$category->id}")
        ->assertForbidden();
});

test('category validation rules are enforced', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/categories', [
            'name' => '',
        ])
        ->assertSessionHasErrors(['name']);

    $this->actingAs($user)
        ->post('/categories', [
            'name' => str_repeat('a', 300), // Too long
        ])
        ->assertSessionHasErrors(['name']);
});

test('category must have name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/categories', [
            'name' => 'Valid Name',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/categories', [
            'name' => 'Valid Name',
        ])
        ->assertRedirect();
});

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


test('system category is identified correctly', function () {
    $user = User::factory()->create();

    $regularCategory = Category::create([
        'name' => 'Regular Category',
        'user_id' => $user->id,
        'is_system' => false,
    ]);

    $systemCategory = Category::create([
        'name' => Category::SYSTEM_CATEGORY_TRANSFER,
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    expect($regularCategory->is_system)->toBeFalse();
    expect($systemCategory->is_system)->toBeTrue();
});

test('category can have transactions', function () {
    $user = User::factory()->create();

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
    ]);

    $wallet = Wallet::create([
        'name' => 'Test Wallet',
        'initial_balance' => 1000,
        'currency' => 'USD',
        'user_id' => $user->id,
    ]);

    $transaction1 = Transaction::create([
        'amount' => 100,
        'description' => 'Transaction 1',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    $transaction2 = Transaction::create([
        'amount' => 200,
        'description' => 'Transaction 2',
        'date' => now(),
        'category_id' => $category->id,
        'wallet_id' => $wallet->id,
    ]);

    expect($category->transactions)->toHaveCount(2);
    expect($category->transactions->pluck('id')->toArray())->toContain($transaction1->id, $transaction2->id);
});

test('system category constant is defined correctly', function () {
    expect(Category::SYSTEM_CATEGORY_TRANSFER)->toBe('Transfer');
});

test('category is cast correctly', function () {
    $user = User::factory()->create();

    $category = Category::create([
        'name' => 'Test Category',
        'user_id' => $user->id,
        'is_system' => true,
    ]);

    expect($category->is_system)->toBeTrue();
});
