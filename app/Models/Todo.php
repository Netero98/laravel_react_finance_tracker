<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $title
 * @property bool $completed
 */
class Todo extends Model
{
    use HasFactory;

    public const PROP_TITLE = 'title';
    public const PROP_COMPLETED = 'completed';

    public const VALIDATION_RULES = [
        Todo::PROP_TITLE => 'required|string|max:65000',
        Todo::PROP_COMPLETED => 'nullable|boolean',
    ];

    protected $fillable = [
        self::PROP_TITLE,
        self::PROP_COMPLETED,
    ];
}
