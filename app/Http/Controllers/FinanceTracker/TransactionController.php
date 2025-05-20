<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(): Response
    {
        $transactions = Transaction::with(['category', 'wallet'])
            ->where('user_id', auth()->id())
            ->orderByDesc('date')
            ->paginate(8);

        $categories = Category::where('user_id', auth()->id())->get();
        $wallets = Wallet::where('user_id', auth()->id())->get();

        return Inertia::render('finance-tracker/transactions/index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'wallets' => $wallets,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category_id' => 'required|exists:categories,id',
            'wallet_id' => 'required|exists:wallets,id',
        ]);

        $validated['user_id'] = auth()->id();

        $transaction = Transaction::query()->create($validated);

        $wallet = Wallet::find($validated['wallet_id']);
        $amount = $validated['type'] === 'income' ? $validated['amount'] : -$validated['amount'];
        $wallet->balance += $amount;
        $wallet->save();

        return redirect()->back();
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category_id' => 'required|exists:categories,id',
            'wallet_id' => 'required|exists:wallets,id',
        ]);

        // Revert old transaction
        $wallet = $transaction->wallet;
        $oldAmount = $transaction->type === 'income' ? -$transaction->amount : $transaction->amount;
        $wallet->balance += $oldAmount;

        // Apply new transaction
        $newAmount = $validated['type'] === 'income' ? $validated['amount'] : -$validated['amount'];

        if ($transaction->wallet_id === $validated['wallet_id']) {
            $wallet->balance += $newAmount;
            $wallet->save();
        } else {
            $wallet->save();
            $newWallet = Wallet::find($validated['wallet_id']);
            $newWallet->balance += $newAmount;
            $newWallet->save();
        }

        $transaction->update($validated);

        return redirect()->back();
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $wallet = $transaction->wallet;
        $amount = $transaction->type === 'income' ? -$transaction->amount : $transaction->amount;
        $wallet->balance += $amount;
        $wallet->save();

        $transaction->delete();

        return redirect()->back();
    }
}
