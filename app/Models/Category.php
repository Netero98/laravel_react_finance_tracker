<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const PROP_NAME = 'name';
    public const PROP_USER_ID = 'user_id';

    protected $fillable = [
        'name',
        'user_id',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
