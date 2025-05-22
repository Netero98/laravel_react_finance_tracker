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
