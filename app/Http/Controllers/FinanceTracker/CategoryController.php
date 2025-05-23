<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()->where('user_id', auth()->id())->paginate(8);

        return Inertia::render('finance-tracker/categories/index', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();

        Category::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update($validated);

        return redirect()->back();
    }

    public function destroy(Category $category)
    {
        if ($category->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $category->delete();

        return redirect()->back();
    }
}
