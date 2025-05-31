<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ExchangeRateService;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * The exchange rate service instance.
     */
    private ExchangeRateService $exchangeRateService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function index()
    {
        $exchangeRates = $this->getExchangeRates();

        $allWalletsWithTransactionsAndCategories = Wallet::with([
            Wallet::RELATION_TRANSACTIONS => function ($query) {
                $query->with(Transaction::RELATION_CATEGORY);
            }
        ])
            ->where('user_id', auth()->id())
            ->get();

        $initialBalance = $allWalletsWithTransactionsAndCategories->reduce(function ($total, Wallet $wallet) use ($exchangeRates) {
            $rate = $exchangeRates[$wallet->currency];

            return $total + $wallet->initial_balance / $rate;
        });

        /**
         * @var Collection $allTransactionsWithCategories
         */
        $allTransactionsWithCategories = $allWalletsWithTransactionsAndCategories->flatMap(function ($wallet) {
            return $wallet->transactions;
        });

        $allTransactionsWithCategories = $allTransactionsWithCategories->sortBy(Transaction::PROP_DATE);

        $currentMonth = now()->startOfMonth();
        $nextMonth = now()->copy()->addMonth()->startOfMonth();

        $currentMonthExpenseTransactionsWithCategories = $allTransactionsWithCategories
            ->where(Transaction::PROP_AMOUNT, '<', 0)
            ->whereBetween(Transaction::PROP_DATE, [$currentMonth, $nextMonth])
            ->filter(function ($transaction) {
                // Exclude transactions with Transfer category
                return $transaction->category->name !== Category::SYSTEM_CATEGORY_TRANSFER;
            });

        $currentMonthIncomeTransactionsWithCategories = $allTransactionsWithCategories
            ->where(Transaction::PROP_AMOUNT, '>', 0)
            ->whereBetween(Transaction::PROP_DATE, [$currentMonth, $nextMonth])
            ->filter(function ($transaction) {
                // Exclude transactions with Transfer category
                return $transaction->category->name !== Category::SYSTEM_CATEGORY_TRANSFER;
            });

        // Calculate cumulative balance history
        $balanceHistory = [];
        $dates = [];

        /**
         * @var User $authenticatedUser
         */
        $authenticatedUser = auth()->user();
        $theFirstPossibleDataForTransaction = $authenticatedUser->created_at->format('Y-m-d') ;
        $dates[$theFirstPossibleDataForTransaction] = $theFirstPossibleDataForTransaction;
        $balanceHistory[$theFirstPossibleDataForTransaction] = $initialBalance;
        $previousBalance = $initialBalance;

        foreach ($allTransactionsWithCategories as $transaction) {
            $date = $transaction->date->format('Y-m-d');
            $amount = $transaction->amount;

            // Convert to USD if needed
            if ($transaction->wallet->currency !== ExchangeRateService::USD) {
                $rate = $exchangeRates[$transaction->wallet->currency] ?? 1;
                $amount = $amount / $rate; // Convert to USD
            }

            // Store the balance for this date
            if (isset($balanceHistory[$date])) {
                $balanceHistory[$date] += $amount;
                $previousBalance = $balanceHistory[$date];
            } else {
                $balanceHistory[$date] = $previousBalance + $amount;
                $previousBalance = $balanceHistory[$date];
                $dates[] = $date;
            }
        }

        // Format for the frontend
        $formattedHistoryUSD = [];
        foreach ($dates as $date) {
            $formattedHistoryUSD[] = [
                'date' => $date,
                'balance' => $balanceHistory[$date]
            ];
        }

        // Calculate current total balance in USD for the authenticated user
        $currentBalanceUSD = $allWalletsWithTransactionsAndCategories->reduce(function ($total, Wallet $wallet) use ($exchangeRates) {
            $rate = $wallet->currency === ExchangeRateService::USD
                ? 1
                : $exchangeRates[$wallet->currency];

            return $total + ($wallet->getInitialBalancePlusTransactionsDelta() / $rate);
        }, 0);

        // Prepare wallet data for pie chart
        $walletData = $allWalletsWithTransactionsAndCategories->map(function (Wallet $wallet) use ($exchangeRates) {
            $rate = $wallet->currency === ExchangeRateService::USD ? 1 : ($exchangeRates[$wallet->currency] ?? 1);
            $balanceUSD = $wallet->getInitialBalancePlusTransactionsDelta() / $rate;

            return [
                'name' => $wallet->name,
                'walletCurrentBalance' => $wallet->getInitialBalancePlusTransactionsDelta(),
                'walletCurrentBalanceUSD' => $balanceUSD,
                'currency' => $wallet->currency,
            ];
        });

        // Group current month expenses by category
        $expensesByCategory = [];

        foreach ($currentMonthExpenseTransactionsWithCategories as $expenseTransaction) {
            $categoryName = $expenseTransaction->category ? $expenseTransaction->category->name : 'Uncategorized';
            $amount = $expenseTransaction->amount;

            // Convert to USD if needed
            if ($expenseTransaction->wallet && $expenseTransaction->wallet->currency !== ExchangeRateService::USD) {
                $rate = $exchangeRates[$expenseTransaction->wallet->currency] ?? 1;
                $amount = $amount / $rate; // Convert to USD
            }

            if (!isset($expensesByCategory[$categoryName])) {
                $expensesByCategory[$categoryName] = 0;
            }

            $expensesByCategory[$categoryName] += $amount;
        }

        // Format for the frontend
        $currentMonthExpensesDataUSD = [];
        foreach ($expensesByCategory as $category => $amount) {
            $currentMonthExpensesDataUSD[] = [
                'name' => $category,
                'amount' => $amount
            ];
        }

        // Group current month incomeTransaction by category
        $incomeByCategory = [];

        foreach ($currentMonthIncomeTransactionsWithCategories as $incomeTransaction) {
            $categoryName = $incomeTransaction->category ? $incomeTransaction->category->name : 'Uncategorized';
            $amount = $incomeTransaction->amount;

            // Convert to USD if needed
            if ($incomeTransaction->wallet && $incomeTransaction->wallet->currency !== ExchangeRateService::USD) {
                $rate = $exchangeRates[$incomeTransaction->wallet->currency] ?? 1;
                $amount = $amount / $rate; // Convert to USD
            }

            if (!isset($incomeByCategory[$categoryName])) {
                $incomeByCategory[$categoryName] = 0;
            }

            $incomeByCategory[$categoryName] += $amount;
        }

        // Format for the frontend
        $currentMonthIncomeDataUSD = [];
        foreach ($incomeByCategory as $category => $amount) {
            $currentMonthIncomeDataUSD[] = [
                'name' => $category,
                'amount' => $amount
            ];
        }

        return Inertia::render('dashboard', [
            'balanceHistoryUSD' => $formattedHistoryUSD,
            'currentBalanceUSD' => $currentBalanceUSD,
            'walletData' => $walletData,
            'currentMonthExpensesUSD' => $currentMonthExpensesDataUSD,
            'currentMonthIncomeUSD' => $currentMonthIncomeDataUSD,
            'exchangeRates' => $exchangeRates,
        ]);
    }

    /**
     * Get exchange rates from the service
     *
     * @return array
     */
    private function getExchangeRates(): array
    {
        return $this->exchangeRateService->getExchangeRates();
    }
}
