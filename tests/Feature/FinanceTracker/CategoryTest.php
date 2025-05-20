<?php

use App\Models\User;
use App\Models\Category;
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
        'type' => 'expense',
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
            'type' => 'income',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('categories', [
        'name' => 'New Category',
        'type' => 'income',
        'user_id' => $user->id,
    ]);
});

test('users can update their category', function () {
    $user = User::factory()->create();

    $category = Category::create([
        'name' => 'Test Category',
        'type' => 'expense',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->put("/categories/{$category->id}", [
            'name' => 'Updated Category',
            'type' => 'income',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Category',
        'type' => 'income',
    ]);
});

test('users can delete their category', function () {
    $user = User::factory()->create();

    $category = Category::create([
        'name' => 'Test Category',
        'type' => 'expense',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Test Category',
        'type' => 'expense',
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
        'type' => 'expense',
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
            'type' => 'income',
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
            'type' => 'invalid-type',
        ])
        ->assertSessionHasErrors(['name', 'type']);

    $this->actingAs($user)
        ->post('/categories', [
            'name' => str_repeat('a', 300), // Too long
            'type' => 'expense',
        ])
        ->assertSessionHasErrors(['name']);
});

test('category type must be income or expense', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/categories', [
            'name' => 'Valid Name',
            'type' => 'savings', // Invalid type
        ])
        ->assertSessionHasErrors(['type']);

    $this->actingAs($user)
        ->post('/categories', [
            'name' => 'Valid Name',
            'type' => 'income', // Valid type
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/categories', [
            'name' => 'Valid Name',
            'type' => 'expense', // Valid type
        ])
        ->assertRedirect();
});
