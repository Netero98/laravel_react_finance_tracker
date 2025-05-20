<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create categories
        $incomeCategories = [
            'Salary', 'Freelance', 'Investments', 'Gifts', 'Rental Income'
        ];

        $expenseCategories = [
            'Groceries', 'Utilities', 'Rent/Mortgage', 'Transportation',
            'Dining Out', 'Entertainment', 'Healthcare', 'Shopping',
            'Travel', 'Education', 'Personal Care'
        ];

        foreach ($incomeCategories as $name) {
            Category::create([
                'name' => $name,
                'type' => 'income',
                'user_id' => 1,
            ]);
        }

        foreach ($expenseCategories as $name) {
            Category::create([
                'name' => $name,
                'type' => 'expense',
                'user_id' => 1,
            ]);
        }

        // Create wallets
        $wallets = [
            ['name' => 'Main Account', 'balance' => 5000, 'currency' => 'USD'],
            ['name' => 'Savings', 'balance' => 10000, 'currency' => 'USD'],
            ['name' => 'Euro Account', 'balance' => 2000, 'currency' => 'EUR'],
            ['name' => 'Investment Account', 'balance' => 15000, 'currency' => 'USD'],
        ];

        foreach ($wallets as $wallet) {
            Wallet::create([
                'name' => $wallet['name'],
                'balance' => $wallet['balance'],
                'currency' => $wallet['currency'],
                'user_id' => 1,
            ]);
        }

        // Create transactions over the past 1 month
        $startDate = Carbon::now()->subMonths(1);
        $endDate = Carbon::now();

        $incomeCategories = Category::where('type', 'income')->get();
        $expenseCategories = Category::where('type', 'expense')->get();
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
                    'type' => 'income',
                    'category_id' => $incomeCategories->where('name', 'Salary')->first()->id,
                    'wallet_id' => $walletIds[0], // Main Account
                    'user_id' => 1,
                ]);
            }

            // Random transactions throughout the month
            $numTransactions = mt_rand(2, 5);
            for ($i = 0; $i < $numTransactions; $i++) {
                // 70% chance of expense, 30% chance of additional income
                $isExpense = mt_rand(1, 10) <= 7;

                if ($isExpense) {
                    // Expense transaction
                    $category = $expenseCategories->random();
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
                    $category = $incomeCategories->random();
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
                    'type' => $isExpense ? 'expense' : 'income',
                    'category_id' => $category->id,
                    'wallet_id' => $wallet_id,
                    'user_id' => 1,
                ]);
            }

            // Move to next day
            $currentDate->addDay();
        }
    }
}
