<?php

namespace App\DTO;

use Illuminate\Support\Collection;

readonly class UserAgregatedFinanceDataDTO
{
    public function __construct(
        public  array $balanceHistoryUSD,
        public int $currentBalanceUSD,
        public array $walletData,
        public array $currentMonthExpensesUSD,
        public array $currentMonthIncomeUSD,
        public array $exchangeRates,
    )
    {
    }
}
