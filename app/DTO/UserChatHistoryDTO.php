<?php

namespace App\DTO;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

readonly class UserChatHistoryDTO implements Arrayable
{
    public function __construct(
        public string $id,
        public string $content,
        public string $role,
        public Carbon $timestamp
    )
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'role' => $this->role,
            'timestamp' => $this->timestamp->toIso8601String()
        ];
    }
}
