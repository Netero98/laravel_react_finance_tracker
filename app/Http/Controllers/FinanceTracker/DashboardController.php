<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use App\Services\UserAgregatedFinanceDataService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
        private readonly UserAgregatedFinanceDataService $userAgregatedFinanceDataService
    ){
    }

    public function index()
    {
        $userData = $this->userAgregatedFinanceDataService->getAllUserAgregatedFinanceData();

        return Inertia::render('dashboard', [
            'balanceHistoryUSD' => $userData->balanceHistoryUSD,
            'currentBalanceUSD' => $userData->currentBalanceUSD,
            'walletData' => $userData->walletData,
            'currentMonthExpensesUSD' => $userData->currentMonthExpensesUSD,
            'currentMonthIncomeUSD' => $userData->currentMonthIncomeUSD,
            'exchangeRates' => $this->getExchangeRates(),
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
