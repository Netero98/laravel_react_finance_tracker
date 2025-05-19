<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Get all transactions ordered by date
        $transactions = Transaction::with(['wallet'])->orderBy('date')->get();

        // Get exchange rates (you'd need to implement this)
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

        // Calculate current total balance in USD
        $currentBalance = Wallet::all()->reduce(function ($total, $wallet) use ($exchangeRates) {
            $rate = $wallet->currency === 'USD' ? 1 : ($exchangeRates[$wallet->currency] ?? 1);
            return $total + ($wallet->balance / $rate);
        }, 0);

        return Inertia::render('dashboard', [
            'balanceHistory' => $formattedHistory,
            'currentBalance' => $currentBalance,
        ]);
    }

    // Example method to get exchange rates (you'd implement this with a real API)
    private function getExchangeRates()
    {
        // In a real app, you would fetch these from a currency API
        // Example: return Http::get('https://api.exchangerate-api.com/v4/latest/USD')->json()['rates'];

        // For demonstration, using static rates
        return [
            'USD' => 1,
            'EUR' => 0.92,
            'GBP' => 0.78,
            'THB' => 35.5,
            'RUB'=> 80.5,
            // Add more currencies as needed
        ];
    }
}
