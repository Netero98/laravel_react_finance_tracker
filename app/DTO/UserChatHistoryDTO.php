<?php

namespace App\DTO;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

readonly class UserChatHistoryDTO implements Arrayable
{
    public function __construct(
        public string $id,
        public string $text,
        public bool   $isUser,
        public Carbon $timestamp
    )
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'isUser' => $this->isUser,
            'timestamp' => $this->timestamp->toIso8601String()
        ];
    }
}
