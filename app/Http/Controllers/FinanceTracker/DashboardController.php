<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
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
        $allWalletsWithTransactionsAndCategories = Wallet::with([
            Wallet::RELATION_TRANSACTIONS => function ($query) {
                $query->with(Transaction::RELATION_CATEGORY);
            }
        ])
            ->where('user_id', auth()->id())
            ->get();

        /**
         * @var Collection $allTransactionsWithCategories
         */
        $allTransactionsWithCategories = $allWalletsWithTransactionsAndCategories->flatMap(function ($wallet) {
            return $wallet->transactions;
        });

        $currentMonth = now()->startOfMonth();
        $nextMonth = now()->copy()->addMonth()->startOfMonth();

        $currentMonthExpenseTransactionsWithCategories = $allTransactionsWithCategories
            ->where(Transaction::PROP_AMOUNT, '<', 0)
            ->whereBetween(Transaction::PROP_DATE, [$currentMonth, $nextMonth]);

        $currentMonthIncomeTransactionsWithCategories = $allTransactionsWithCategories
            ->where(Transaction::PROP_AMOUNT, '>', 0)
            ->whereBetween(Transaction::PROP_DATE, [$currentMonth, $nextMonth]);

        $exchangeRates = $this->getExchangeRates();

        // Calculate cumulative balance history
        $balanceHistory = [];
        $runningBalance = 0;
        $dates = [];

        foreach ($allTransactionsWithCategories as $transaction) {
            $date = $transaction->date->format('Y-m-d');
            $amount = $transaction->amount;

            // Convert to USD if needed
            if ($transaction->wallet->currency !== ExchangeRateService::USD) {
                $rate = $exchangeRates[$transaction->wallet->currency] ?? 1;
                $amount = $amount / $rate; // Convert to USD
            }

            $runningBalance += $amount;

            // Store the balance for this date
            if (isset($balanceHistory[$date])) {
                $balanceHistory[$date] = $runningBalance;
            } else {
                $balanceHistory[$date] = $runningBalance;
                $dates[] = $date;
            }
        }

        // Format for the frontend
        $formattedHistory = [];
        foreach ($dates as $date) {
            $formattedHistory[] = [
                'date' => $date,
                'balance' => $balanceHistory[$date]
            ];
        }

        // Calculate current total balance in USD for the authenticated user
        $currentBalance = $allWalletsWithTransactionsAndCategories->reduce(function ($total, Wallet $wallet) use ($exchangeRates) {
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
        $currentMonthExpensesData = [];
        foreach ($expensesByCategory as $category => $amount) {
            $currentMonthExpensesData[] = [
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
        $currentMonthIncomeData = [];
        foreach ($incomeByCategory as $category => $amount) {
            $currentMonthIncomeData[] = [
                'name' => $category,
                'amount' => $amount
            ];
        }

        return Inertia::render('dashboard', [
            'balanceHistory' => $formattedHistory,
            'currentBalance' => $currentBalance,
            'walletData' => $walletData,
            'currentMonthExpenses' => $currentMonthExpensesData,
            'currentMonthIncome' => $currentMonthIncomeData,
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
