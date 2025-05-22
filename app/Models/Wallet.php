<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property $initial_balance
 * @property $currency
 * @property $user_id
 *
 * @property-read $transactions
 */
class Wallet extends Model
{
    public const PROP_NAME = 'name';
    public const PROP_INITIAL_BALANCE = 'initial_balance';
    public const PROP_CURRENCY = 'currency';
    public const PROP_USER_ID = 'user_id';
    public const RELATION_TRANSACTIONS = 'transactions';
    private ?int $currentBalanceRuntimeCache = null;

    protected $fillable = [
        'name',
        'initial_balance',
        'balance',
        'currency',
        'user_id',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getInitialBalancePlusTransactionsDelta()
    {
        if ($this->currentBalanceRuntimeCache !== null) {
            return $this->currentBalanceRuntimeCache;
        }

        return $this->currentBalanceRuntimeCache = $this->initial_balance + $this->transactions->sum(Transaction::PROP_AMOUNT);
    }
}
