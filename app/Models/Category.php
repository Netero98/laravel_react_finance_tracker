<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const PROP_NAME = 'name';
    public const PROP_USER_ID = 'user_id';
    public const PROP_IS_SYSTEM = 'is_system';

    public const SYSTEM_CATEGORY_TRANSFER = 'Transfer';

    protected $fillable = [
        'name',
        'user_id',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
