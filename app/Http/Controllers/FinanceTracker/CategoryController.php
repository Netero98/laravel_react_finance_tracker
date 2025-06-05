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
        $categories = Category::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate(8);

        return Inertia::render('finance-tracker/categories/index', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,NULL,id,user_id,' . auth()->id(),
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

        if ($category->is_system) {
            return redirect()->back()->withErrors(['category' => 'System categories cannot be modified.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id . ',id,user_id,' . auth()->id(),
        ]);

        $category->update($validated);

        return redirect()->back();
    }

    public function destroy(Category $category)
    {
        if ($category->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($category->is_system) {
            return redirect()->back()->withErrors(['category' => 'System categories cannot be deleted.']);
        }

        $category->delete();

        return redirect()->back();
    }
}
