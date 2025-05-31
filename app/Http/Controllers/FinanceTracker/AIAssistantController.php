<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\AiChatHistory;
use App\Services\ExchangeRateService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AIAssistantController extends Controller
{
    /**
     * The exchange rate service instance.
     */
    private ExchangeRateService $exchangeRateService;

    /**
     * The OpenAI service instance.
     */
    private OpenAIService $openAIService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ExchangeRateService $exchangeRateService, OpenAIService $openAIService)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->openAIService = $openAIService;
    }

    /**
     * Display the AI assistant page.
     */
    public function index(): Response
    {
        $userChatHistory = AiChatHistory::query()->where('user_id', auth()->id())->get();

        return Inertia::render('finance-tracker/ai-assistant/index', [
            'chatHistory' => $userChatHistory->data
        ]);
    }

    /**
     * Process a chat message and return a response.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'chatHistory' => 'required|string',
        ]);

        $chatHistory = $request->input('chatHistory');

        // Call the OpenAI service to generate a response
        $response = $this->openAIService->getChatHistoryWithAiAnswer($chatHistory);

        return back()->with('chatHistory', $response);
    }

    /**
     * Get exchange rates from the service
     *
     * @return array
     */
    private function getExchangeRates(): array
    {
        return $this->exchangeRateService->getExchangeRates();
    }
}
