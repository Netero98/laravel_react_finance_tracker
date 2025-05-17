<?php

namespace Database\Factories;

use App\Models\Todo;
use Illuminate\Database\Eloquent\Factories\Factory;

class TodoFactory extends Factory
{
    protected $model = Todo::class;

    public function definition(): array
    {
        return [
            Todo::PROP_TITLE => fake()->text(),
            Todo::PROP_COMPLETED => false,
        ];
    }
}
