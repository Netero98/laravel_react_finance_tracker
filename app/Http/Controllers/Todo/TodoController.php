<?php

namespace App\Http\Controllers\Todo;

use App\Models\Todo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class TodoController extends Controller
{
    public function index()
    {
        return Inertia::render('todo/index', [
            'todos' => Todo::orderBy('created_at', 'desc')->get()
        ]);
    }

    public function toggleCompleted(Todo $todo)
    {
        $todo->completed = !$todo->completed;
        $todo->save();

        return redirect()->back();
    }

    public function store(Request $request)
    {
        $validated = $request->validate(Todo::VALIDATION_RULES);

        Todo::create($validated);

        return redirect()->back();
    }

    public function update(Todo $todo, FormRequest $request)
    {
        $validated = $request->validate(Todo::VALIDATION_RULES);

        $todo->update($validated);

        return redirect()->back();
    }

    public function destroy(Todo $todo)
    {
        $todo->delete();

        return redirect()->back();
    }
}
