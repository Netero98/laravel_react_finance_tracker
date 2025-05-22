<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Wallet $wallet
 */
class Transaction extends Model
{
    public const RELATION_CATEGORY = 'category';
    public const RELATION_WALLET = 'wallet';

    public const PROP_WALLET_ID = 'wallet_id';
    public const PROP_CATEGORY_ID = 'category_id';
    public const PROP_AMOUNT = 'amount';
    public const PROP_DESCRIPTION = 'description';
    public const PROP_DATE = 'date';

    protected $fillable = [
        'amount',
        'description',
        'date',
        'category_id',
        'wallet_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
