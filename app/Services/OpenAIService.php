<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\UserChatHistoryDTO;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Throwable;

class OpenAIService
{
    public function __construct(
        private readonly UserAgregatedFinanceDataService $userAgregatedFinanceDataService,
    ) {
    }

    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    /**
     * @param Collection<UserChatHistoryDTO> $chatHistory
     */

    public function getChatHistoryWithAiAnswer(Collection $chatHistory): Collection
    {
        $userFinanceDataContext = $this->userAgregatedFinanceDataService->getAllUserAgregatedFinanceData();

        $userFinanceDataContext = [
            'currentBalance' => $userFinanceDataContext->currentBalanceUSD,
            'currentMonthExpensesUSD' => $userFinanceDataContext->currentMonthExpensesUSD,
            'currentMonthIncomeUSD' => $userFinanceDataContext->currentMonthIncomeUSD,
            'walletData' => $userFinanceDataContext->walletData,
        ];

        try {
            Log::info('Sending message to OpenAI API');
            return $this->getFreshChatHistoryWithNewLastMessageFromAI($chatHistory, $userFinanceDataContext);
        } catch (Throwable $e) {
            Log::error('Exception in OpenAI API call: ' . $e->getMessage());

            $chatHistory[] = new UserChatHistoryDTO(
                id: UUID::uuid1()->toString(),
                text: 'Something went wrong :( Please, try again later...',
                isUser: false,
                timestamp: new Carbon()
            );

            return $chatHistory;
        }
    }

    /**
     * @return Collection<UserChatHistoryDTO>
     * @throws ConnectionException
     * @throws Exception
     */
    private function getFreshChatHistoryWithNewLastMessageFromAI(Collection $chatHistory, array $userFinanceDataContext): Collection
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model');

        if (empty($apiKey)) {
            throw new Exception('OpenAI API key is not configured');
        }

        $allContextForAi = $userFinanceDataContext;
        $allContextForAi['chatHistory'] = $chatHistory->toArray();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post(self::API_URL, [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful financial assistant. Answer user using the following data (your answer will be added as new chatHistory message): '
                    . json_encode($allContextForAi, JSON_PRETTY_PRINT)
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['choices'][0]['message']['content'])) {
                $chatHistory[] = new UserChatHistoryDTO(
                    id: UUID::uuid1()->toString(),
                    text: $responseData['choices'][0]['message']['content'],
                    isUser: false,
                    timestamp: new Carbon()
                );

                return $chatHistory;
            }
            throw new Exception('Unexpected response format from OpenAI API');
        }

        Log::error('OpenAI API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        throw new Exception('Failed to get response from OpenAI API: ' . $response->status());
    }
}
