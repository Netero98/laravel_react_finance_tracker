<?php

declare(strict_types=1);

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletCollection;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Wallet;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * The exchange rate service instance.
     */
    private ExchangeRateService $exchangeRateService;

    private const PARAM_AMOUNT = 'amount';
    private const PARAM_DESCRIPTION = 'description';
    private const PARAM_DATE = 'date';
    private const PARAM_CATEGORY_ID = 'category_id';
    private const PARAM_WALLET_ID = 'wallet_id';
    private const PARAM_FROM_WALLET_ID = 'from_wallet_id';
    private const PARAM_TO_WALLET_ID = 'to_wallet_id';
    private const PARAM_FROM_AMOUNT = 'from_amount';
    private const PARAM_TO_AMOUNT = 'to_amount';

    /**
     * Create a new controller instance.
     */
    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function index(): Response
    {
        $wallets = Wallet::where('user_id', auth()->id())->get();
        $walletIds = $wallets->pluck('id');

        $transactions = Transaction::with(Transaction::RELATION_CATEGORY, Transaction::RELATION_WALLET)
            ->whereIn(Transaction::PROP_WALLET_ID, $walletIds)
            ->orderByDesc('date')
            ->paginate(8);

        $categories = Category::where('user_id', auth()->id())->get();

        $exchangeRates = $this->exchangeRateService->getExchangeRates();

        return Inertia::render('finance-tracker/transactions/index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'wallets' => new WalletCollection($wallets),
            'exchangeRates' => $exchangeRates,
        ]);
    }

    public function storeTransfer(Request $request)
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

        if (!$isTransfer) {
            return back()->withErrors(['category_id' => 'To store ordinary transaction use "store" endpoint']);
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:65000',
            'date' => 'required|date',
            'category_id' => ['required', Rule::in($allUserCategoryIds)],
            'from_wallet_id' => ['required', Rule::in($allUserWalletIds)],
            'to_wallet_id' => ['required', Rule::in($allUserWalletIds), 'different:from_wallet_id'],
            'from_amount' => 'required|numeric',
            'to_amount' => 'required|numeric',
        ]);

        $fromWalletId = $validated['from_wallet_id'];
        $toWalletId = $validated['to_wallet_id'];

        // Validate that from_wallet_id and to_wallet_id are different
        if ($fromWalletId === $toWalletId) {
            return back()->withErrors(['to_wallet_id' => 'FROM and TO wallets must be different']);
        }

        // Create outgoing transaction (negative amount)
        Transaction::query()->create([
            Transaction::PROP_AMOUNT => -abs($validated['from_amount']),
            Transaction::PROP_DESCRIPTION => $validated['description'],
            Transaction::PROP_DATE => $validated['date'],
            Transaction::PROP_CATEGORY_ID => $validated['category_id'],
            Transaction::PROP_WALLET_ID => $fromWalletId,
        ]);

        // Create incoming transaction (positive amount)
        Transaction::query()->create([
            Transaction::PROP_AMOUNT => abs($validated['to_amount']),
            Transaction::PROP_DESCRIPTION => $validated['description'],
            Transaction::PROP_DATE => $validated['date'],
            Transaction::PROP_CATEGORY_ID => $validated['category_id'],
            Transaction::PROP_WALLET_ID => $validated['to_wallet_id'],
        ]);

        return redirect()->back();
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
            return back()->withErrors(['category_id' => 'To store transfer transaction use "storeTransfer" endpoint']);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'nullable|string|max:65000',
            'date' => 'required|date',
            'category_id' => ['required', Rule::in($allUserCategoryIds)],
            'wallet_id' => ['required', Rule::in($allUserWalletIds)],
        ]);

        Transaction::query()->create($validated);

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
