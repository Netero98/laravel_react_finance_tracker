<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array data
 */
class AiChatHistory extends Model
{
    public const PROP_USER_ID = 'user_id';
    public const PROP_DATA = 'data';

    protected $fillable = [
        'user_id',
        'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];
}
