<?php

namespace App\DTO;

use Illuminate\Support\Collection;

readonly class UserAgregatedFinanceDataDTO
{
    public function __construct(
        public  array $balanceHistoryUSD,
        public float $currentBalanceUSD,
        public Collection $walletData,
        public array $currentMonthExpensesUSD,
        public array $currentMonthIncomeUSD,
        public array $exchangeRates,
    )
    {
    }
}
