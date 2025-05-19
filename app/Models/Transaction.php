<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'amount',
        'description',
        'date',
        'type', // 'income' or 'expense'
        'category_id',
        'wallet_id',
        'user_id',
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