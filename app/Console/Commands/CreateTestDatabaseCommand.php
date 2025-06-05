<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateTestDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recreate-test-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создает тестовую базу данных, если она еще не существует';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $testDbName ='app_testing';

        // Проверяем существование базы данных
        $dbExists = DB::select("SELECT 1 FROM pg_database WHERE datname = '$testDbName'");

        if (empty($dbExists)) {
            $this->info("Создание тестовой базы данных '$testDbName'...");
            DB::statement("CREATE DATABASE $testDbName");
            $this->info("Тестовая база данных '$testDbName' успешно создана.");
        } else {
            $this->info("Удаляем старую тестовую '$testDbName'...");
            DB::statement("DROP DATABASE $testDbName");
            DB::statement("CREATE DATABASE $testDbName");
            $this->info("Тестовая база данных '$testDbName' успешно создана.");
        }

        return Command::SUCCESS;
    }
}
