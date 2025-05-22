<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\ExchangeRateService;
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
        // Get all transactions for the authenticated user ordered by date
        $transactions = Transaction::with(['wallet'])
            ->where('user_id', auth()->id())
            ->orderBy('date')
            ->get();

        // Get current month expense transactions
        $currentMonth = now()->startOfMonth();
        $nextMonth = now()->copy()->addMonth()->startOfMonth();

        $currentMonthExpenses = Transaction::with(['category'])
            ->where('user_id', auth()->id())
            ->where('type', 'expense')
            ->whereBetween('date', [$currentMonth, $nextMonth])
            ->get();

        $exchangeRates = $this->getExchangeRates();

        // Calculate cumulative balance history
        $balanceHistory = [];
        $runningBalance = 0;
        $dates = [];

        foreach ($transactions as $transaction) {
            $date = $transaction->date->format('Y-m-d');
            $amount = $transaction->amount;

            // Convert to USD if needed
            if ($transaction->wallet->currency !== 'USD') {
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
        $wallets = Wallet::where('user_id', auth()->id())->get();
        $currentBalance = $wallets->reduce(function ($total, $wallet) use ($exchangeRates) {
            $rate = $wallet->currency === 'USD' ? 1 : ($exchangeRates[$wallet->currency] ?? 1);
            return $total + ($wallet->balance / $rate);
        }, 0);

        // Prepare wallet data for pie chart
        $walletData = $wallets->map(function ($wallet) use ($exchangeRates) {
            $rate = $wallet->currency === 'USD' ? 1 : ($exchangeRates[$wallet->currency] ?? 1);
            $balanceUSD = $wallet->balance / $rate;

            return [
                'name' => $wallet->name,
                'balance' => $balanceUSD,
                'currency' => $wallet->currency,
                'originalBalance' => $wallet->balance
            ];
        });

        // Group current month expenses by category
        $expensesByCategory = [];
        foreach ($currentMonthExpenses as $expense) {
            $categoryName = $expense->category ? $expense->category->name : 'Uncategorized';
            $amount = $expense->amount;

            // Convert to USD if needed
            if ($expense->wallet && $expense->wallet->currency !== 'USD') {
                $rate = $exchangeRates[$expense->wallet->currency] ?? 1;
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

        // Get current month income transactions
        $currentMonthIncome = Transaction::with(['category'])
            ->where('user_id', auth()->id())
            ->where('type', 'income')
            ->whereBetween('date', [$currentMonth, $nextMonth])
            ->get();

        // Group current month income by category
        $incomeByCategory = [];
        foreach ($currentMonthIncome as $income) {
            $categoryName = $income->category ? $income->category->name : 'Uncategorized';
            $amount = $income->amount;

            // Convert to USD if needed
            if ($income->wallet && $income->wallet->currency !== 'USD') {
                $rate = $exchangeRates[$income->wallet->currency] ?? 1;
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
