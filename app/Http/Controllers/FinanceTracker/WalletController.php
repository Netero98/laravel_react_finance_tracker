<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletCollection;
use App\Models\Wallet;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(): Response
    {
        $wallets = Wallet::query()->where('user_id', auth()->id())->paginate(8);

        return Inertia::render('finance-tracker/wallets/index', [
            'wallets' => (new WalletCollection($wallets))
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:wallets,name,NULL,id,user_id,' . auth()->id()],
            'initial_balance' => 'required|numeric|min:0',
            'currency' => ['required', 'string', 'in:' . implode(',', ExchangeRateService::ALL_EXTERNAL_CURRENCIES)],
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
            'initial_balance' => 'required|numeric|min:0',
            'currency' => ['required', 'string', 'in:' . implode(',', ExchangeRateService::ALL_EXTERNAL_CURRENCIES)],
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
