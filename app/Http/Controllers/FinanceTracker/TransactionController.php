<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(): Response
    {
        $wallets = Wallet::where('user_id', auth()->id())->get();
        $walletIds = $wallets->pluck('id');

        $transactions = Transaction::with(Transaction::RELATION_CATEGORY, Transaction::RELATION_WALLET)
            ->whereIn(Transaction::PROP_WALLET_ID, $walletIds)
            ->orderByDesc('date')
            ->paginate(8);

        $categories = Category::where('user_id', auth()->id())->get();

        return Inertia::render('finance-tracker/transactions/index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'wallets' => $wallets,
        ]);
    }

    public function store(Request $request)
    {
        $allUserCategoryIds = Category::query()->where(Category::PROP_USER_ID, auth()->id())->pluck('id');
        $allUserWalletIds = Wallet::query()->where(Wallet::PROP_USER_ID, auth()->id())->pluck('id');

        // Check if this is a transfer transaction
        $selectedCategory = Category::query()
            ->whereKey($request->category_id)
            ->where(Category::PROP_USER_ID, auth()->id())
            ->first();

        $isTransfer = $selectedCategory &&
                      $selectedCategory->name === Category::SYSTEM_CATEGORY_TRANSFER &&
                      $selectedCategory->is_system;

        if ($isTransfer) {
            $validated = $request->validate([
                'amount' => 'required|numeric',
                'description' => 'nullable|string|max:65000',
                'date' => 'required|date',
                'category_id' => ['required', Rule::in($allUserCategoryIds)],
                'from_wallet_id' => ['required', Rule::in($allUserWalletIds)],
                'to_wallet_id' => ['required', Rule::in($allUserWalletIds), 'different:from_wallet_id'],
            ]);

            // Create outgoing transaction (negative amount)
            Transaction::query()->create([
                'amount' => -abs($validated['amount']),
                'description' => $validated['description'],
                'date' => $validated['date'],
                'category_id' => $validated['category_id'],
                'wallet_id' => $validated['from_wallet_id'],
            ]);

            // Create incoming transaction (positive amount)
            Transaction::query()->create([
                'amount' => abs($validated['amount']),
                'description' => $validated['description'],
                'date' => $validated['date'],
                'category_id' => $validated['category_id'],
                'wallet_id' => $validated['to_wallet_id'],
            ]);
        } else {
            $validated = $request->validate([
                'amount' => 'required|numeric',
                'description' => 'nullable|string|max:65000',
                'date' => 'required|date',
                'category_id' => ['required', Rule::in($allUserCategoryIds)],
                'wallet_id' => ['required', Rule::in($allUserWalletIds)],
            ]);

            Transaction::query()->create($validated);
        }

        return redirect()->back();
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->wallet->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $categoryIds = Category::query()->where(Category::PROP_USER_ID, auth()->id())->pluck('id');
        $walletIds = Wallet::query()->where(Wallet::PROP_USER_ID, auth()->id())->pluck('id');

        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'date' => 'required|date',
            'category_id' => ['required', Rule::in($categoryIds)],
            'wallet_id' => ['required', Rule::in($walletIds)],
        ]);

        $transaction->update($validated);

        return redirect()->back();
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->wallet->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->delete();

        return redirect()->back();
    }
}
