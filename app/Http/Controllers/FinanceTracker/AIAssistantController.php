<?php

namespace App\Http\Controllers\FinanceTracker;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AIAssistantController extends Controller
{
    /**
     * The exchange rate service instance.
     */
    private ExchangeRateService $exchangeRateService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Display the AI assistant page.
     */
    public function index()
    {
        $exchangeRates = $this->getExchangeRates();

        // Get user's wallets with transactions
        $wallets = Wallet::with(['transactions'])
            ->where('user_id', auth()->id())
            ->get();

        // Calculate total balance
        $totalBalance = $wallets->reduce(function ($total, Wallet $wallet) use ($exchangeRates) {
            $rate = $exchangeRates[$wallet->currency];
            return $total + $wallet->getInitialBalancePlusTransactionsDelta() / $rate;
        }, 0);

        // Get recent transactions
        $recentTransactions = Transaction::with(['category', 'wallet'])
            ->whereIn('wallet_id', $wallets->pluck('id'))
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('finance-tracker/ai-assistant/index', [
            'totalBalance' => $totalBalance,
            'wallets' => $wallets,
            'recentTransactions' => $recentTransactions,
            'exchangeRates' => $exchangeRates,
        ]);
    }

    /**
     * Process a chat message and return a response.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $userMessage = $request->input('message');

        // In a real implementation, this would call an AI service
        // For now, we'll return some simple responses based on keywords
        $response = $this->generateResponse($userMessage);

        return response()->json([
            'response' => $response,
        ]);
    }

    /**
     * Generate a simple response based on the user's message.
     * In a real implementation, this would be replaced with an actual AI service.
     */
    private function generateResponse(string $message): string
    {
        $message = strtolower($message);

        if (str_contains($message, 'saving') || str_contains($message, 'save')) {
            return "To improve your savings, consider the 50/30/20 rule: 50% of income for needs, 30% for wants, and 20% for savings. Would you like more specific advice based on your spending patterns?";
        }

        if (str_contains($message, 'invest')) {
            return "Based on your current financial situation, you might consider investing in low-risk options like index funds or ETFs. Would you like me to explain more about these investment options?";
        }

        if (str_contains($message, 'expense') || str_contains($message, 'spending')) {
            return "I can analyze your spending patterns to identify areas where you might be able to reduce expenses. Would you like me to do that?";
        }

        if (str_contains($message, 'budget')) {
            return "Creating a budget is a great way to manage your finances. Based on your income and spending patterns, I can help you create a personalized budget. Would you like me to do that?";
        }

        // Default response
        return "I'm your AI financial assistant. I can help you with understanding your finances, creating budgets, saving strategies, and investment advice. What would you like to know?";
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
