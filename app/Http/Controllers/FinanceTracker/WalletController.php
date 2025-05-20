<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(): Response
    {
        $wallets = Wallet::query()->where('user_id', auth()->id())->paginate(8);

        return Inertia::render('finance-tracker/wallets/index', [
            'wallets' => $wallets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
        ]);

        $validated['user_id'] = auth()->id();

        Wallet::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
        ]);

        $wallet->update($validated);

        return redirect()->back();
    }

    public function destroy(Wallet $wallet)
    {
        if ($wallet->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $wallet->delete();

        return redirect()->back();
    }
}
