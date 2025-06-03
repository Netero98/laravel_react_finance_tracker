<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTO\UserChatHistoryDTO;
use Illuminate\Support\Collection;

interface AiChatInterface
{
    /**
     * Get chat history with AI answer appended
     *
     * @param Collection<UserChatHistoryDTO> $chatHistory
     * @return Collection<UserChatHistoryDTO>
     */
    public function getChatHistoryWithAiAnswer(Collection $chatHistory): Collection;
}
