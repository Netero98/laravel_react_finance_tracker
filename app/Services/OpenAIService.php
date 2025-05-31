<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAIService
{
    public function __construct(
        private readonly UserAgregatedFinanceDataService $userAgregatedFinanceDataService,
    )
    {

    }

    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    private const CACHE_KEY = 'openai_responses';
    public const CACHE_TTL = 3600; // 1 hour

    /**
     * Generate a response using OpenAI API
     *
     * @param string $message The user's message
     * @return fresh chat history
     */
    public function getChatHistoryWithAiAnswer(array $chatHistory): array
    {
        $userFinanceDataContext = $this->userAgregatedFinanceDataService->getAllUserAgregatedFinanceData();

        $userFinanceDataContext = [
            'currentBalance' => $userFinanceDataContext->currentBalanceUSD,
            'currentMonthExpensesUSD' => $userFinanceDataContext->currentMonthExpensesUSD,
            'currentMonthIncomeUSD' => $userFinanceDataContext->currentMonthIncomeUSD,
        ];

        try {
            Log::info('Sending message to OpenAI API');
            return $this->callOpenAiWithUserDataContext($chatHistory, $userFinanceDataContext);
        } catch (Throwable $e) {
            Log::error('Exception in OpenAI API call: ' . $e->getMessage());
            return $this->getFallbackResponse();
        }
    }

    /**
     * Call the OpenAI API
     * @return array chat history with new ai response
     *
     * @throws Throwable
     *
     *
     */
    private function callOpenAiWithUserDataContext(array $chatHistory, array $userFinanceDataContext): array
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model');

        if (empty($apiKey)) {
            throw new Exception('OpenAI API key is not configured');
        }


        $allContextForAi = $userFinanceDataContext;
        $allContextForAi['chatHistory'] = $chatHistory;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post(self::API_URL, [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful financial assistant. Help user with his question using the following data:'
                    . json_encode($allContextForAi, JSON_PRETTY_PRINT)
                ],
//                [
//                    'role' => 'user',
//                    'content' => $message
//                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['choices'][0]['message']['content'])) {
                return $responseData['choices'][0]['message']['content'];
            }
            throw new Exception('Unexpected response format from OpenAI API');
        }

        Log::error('OpenAI API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        throw new Exception('Failed to get response from OpenAI API: ' . $response->status());
    }

    /**
     * Get a fallback response if the API call fails
     *
     * @param string $message The user's message
     * @return string A fallback response
     */
    private function getFallbackResponse(): string
    {
        // Default response
        return "I'm your AI financial assistant. I can help you with understanding your finances, creating budgets, saving strategies, and investment advice. What would you like to know?";
    }
}
