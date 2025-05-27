<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    private const LIGHT_MODE = false;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'created_at' => Carbon::now()->subMonths(2),
        ]);

        // Create system Transfer category
        $transferCategory = Category::create([
            'name' => Category::SYSTEM_CATEGORY_TRANSFER,
            'user_id' => 1,
            'is_system' => true,
        ]);

        if (self::LIGHT_MODE) {
            return;
        }

        // Create categories
        $incomeCategories = new Collection([
            'Salary', 'Freelance', 'Investments', 'Gifts', 'Rental Income'
        ]);

        $expenseCategories = new Collection([
            'Groceries', 'Utilities', 'Rent/Mortgage', 'Transportation',
            'Dining Out', 'Entertainment', 'Healthcare', 'Shopping',
            'Travel', 'Education', 'Personal Care'
        ]);

        $savedIncomeCategories = new Collection();
        $savedExpenseCategories = new Collection();

        foreach ($incomeCategories as $name) {
            $savedIncomeCategories[] = Category::create([
                'name' => $name,
                'user_id' => 1,
            ]);
        }

        foreach ($expenseCategories as $name) {
            $savedExpenseCategories[] = Category::create([
                'name' => $name,
                'user_id' => 1,
            ]);
        }

        // Create wallets
        $wallets = [
            ['name' => 'Main Account', 'initial_balance' => 576, 'currency' => 'USD'],
            ['name' => 'Savings', 'initial_balance' => 43624, 'currency' => 'THB'],
            ['name' => 'Euro Account', 'initial_balance' => 1054, 'currency' => 'EUR'],
            ['name' => 'Investment Account', 'initial_balance' => 235547, 'currency' => 'RUB'],
        ];

        foreach ($wallets as $wallet) {
            Wallet::create([
                'name' => $wallet['name'],
                'initial_balance' => $wallet['initial_balance'],
                'currency' => $wallet['currency'],
                'user_id' => 1,
            ]);
        }

        // Create transactions over the past 1 month
        $startDate = Carbon::now()->subMonths(1);
        $endDate = Carbon::now();
        $walletIds = Wallet::pluck('id')->toArray();

        // Generate transactions
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            // Monthly salary (first of each month)
            if ($currentDate->day === 1 || ($currentDate->eq($startDate) && $currentDate->day < 5)) {
                Transaction::create([
                    'amount' => mt_rand(3500, 4500),
                    'description' => 'Monthly Salary',
                    'date' => $currentDate->format('Y-m-d'),
                    'category_id' => $savedIncomeCategories->where('name', 'Salary')->first()->id,
                    'wallet_id' => $walletIds[0], // Main Account
                ]);
            }

            // Random transactions throughout the month
            $numTransactions = mt_rand(2, 5);
            for ($i = 0; $i < $numTransactions; $i++) {
                $isExpense = mt_rand(1, 10) <= 4;

                if ($isExpense) {
                    // Expense transaction
                    $category = $savedExpenseCategories->random();
                    $amount = -mt_rand(10, 500); // Negative for expenses

                    // Larger expenses occasionally
                    if (mt_rand(1, 10) === 1) {
                        $amount = -mt_rand(500, 2000);
                    }

                    $description = match($category->name) {
                        'Groceries' => ['Walmart', 'Target', 'Aldi', 'Whole Foods', 'Costco'][mt_rand(0, 4)],
                        'Utilities' => ['Electric Bill', 'Water Bill', 'Internet', 'Phone Bill'][mt_rand(0, 3)],
                        'Dining Out' => ['Restaurant', 'Cafe', 'Fast Food', 'Takeout'][mt_rand(0, 3)],
                        'Transportation' => ['Gas', 'Uber', 'Public Transit', 'Car Maintenance'][mt_rand(0, 3)],
                        default => $category->name,
                    };
                } else {
                    // Income transaction
                    $category = $savedIncomeCategories->random();
                    $amount = mt_rand(50, 500);

                    // Occasionally larger income
                    if (mt_rand(1, 10) === 1) {
                        $amount = mt_rand(500, 1500);
                    }

                    $description = match($category->name) {
                        'Freelance' => 'Freelance Project',
                        'Investments' => 'Investment Return',
                        'Gifts' => 'Gift Received',
                        'Rental Income' => 'Rental Payment',
                        default => $category->name,
                    };
                }

                // Select wallet (with higher chance for main account)
                $wallet_id = mt_rand(1, 10) <= 7 ? $walletIds[0] : $walletIds[array_rand($walletIds)];

                Transaction::create([
                    'amount' => $amount,
                    'description' => $description,
                    'date' => $currentDate->format('Y-m-d'),
                    'category_id' => $category->id,
                    'wallet_id' => $wallet_id,
                ]);
            }

            // Move to next day
            $currentDate->addDay();
        }

        // Add some transfer transactions between wallets
        $transferDates = [
            Carbon::now()->subDays(20),
            Carbon::now()->subDays(15),
            Carbon::now()->subDays(10),
            Carbon::now()->subDays(5),
        ];

        foreach ($transferDates as $date) {
            // Create a pair of transactions for each transfer (one negative, one positive)
            $amount = mt_rand(100, 1000);
            $fromWalletId = $walletIds[array_rand($walletIds)];

            // Make sure we pick a different wallet for the destination
            do {
                $toWalletId = $walletIds[array_rand($walletIds)];
            } while ($toWalletId === $fromWalletId);

            // Negative transaction (money leaving the source wallet)
            Transaction::create([
                'amount' => -$amount,
                'description' => 'Transfer to ' . Wallet::find($toWalletId)->name,
                'date' => $date->format('Y-m-d'),
                'category_id' => $transferCategory->id,
                'wallet_id' => $fromWalletId,
            ]);

            // Positive transaction (money entering the destination wallet)
            Transaction::create([
                'amount' => $amount,
                'description' => 'Transfer from ' . Wallet::find($fromWalletId)->name,
                'date' => $date->format('Y-m-d'),
                'category_id' => $transferCategory->id,
                'wallet_id' => $toWalletId,
            ]);
        }
    }
}
