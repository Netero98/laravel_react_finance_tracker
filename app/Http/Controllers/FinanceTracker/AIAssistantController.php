<?php

namespace App\Http\Controllers\FinanceTracker;

use App\DTO\UserChatHistoryDTO;
use App\Http\Controllers\Controller;
use App\Models\AiChatHistory;
use App\Services\ExchangeRateService;
use App\Services\OpenAIService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Ramsey\Uuid\Uuid;

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
        /**
         * @var AiChatHistory $userChatHistory
         */
        $userChatHistory = AiChatHistory::query()->createOrFirst([
            'user_id' => auth()->id(),
        ]);

        if (empty($userChatHistory->data)) {
            $initialSystemMessage = new UserChatHistoryDTO(
                id: Uuid::uuid1()->toString(),
                text: 'Hi! I am your personal AI assistant. I can help you with your finances. How can I help you today?',
                isUser: false,
                timestamp: new Carbon()
            );

            $userChatHistory->data = [$initialSystemMessage->toArray()];
            $userChatHistory->save();
        }

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
            'chatHistory' => 'required',
        ]);

        $chatHistoryPlain = $request->input('chatHistory');
        $chatHistory = new Collection();

        foreach ($chatHistoryPlain as $chatHistoryArr) {
            $chatHistory[] = new UserChatHistoryDTO(
                id: $chatHistoryArr['id'],
                text: $chatHistoryArr['text'],
                isUser: $chatHistoryArr['isUser'],
                timestamp: new Carbon($chatHistoryArr['timestamp'])
            );
        }

        $aiChatModel = AiChatHistory::query()->where([
            AiChatHistory::PROP_USER_ID => auth()->id(),
        ])->first();

        $aiChatModel->data = $chatHistory->toArray();
        $aiChatModel->save();

        $chatHistoryWithNewLastMessageFromAI = $this->openAIService->getChatHistoryWithAiAnswer($chatHistory);

        $aiChatModel->data = $chatHistoryWithNewLastMessageFromAI->toArray();
        $aiChatModel->save();

        return back();
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
