<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class CreateTestDatabaseCommand extends Command
{
    protected $signature = 'app:recreate-test-database';
    protected $description = 'Создает\пересоздает с нуля тестовую базу данных';

    public function handle()
    {
        $testDbName = 'app_testing';

        // Сохраняем текущие настройки подключения
        $originalConnection = Config::get('database.default');
        $originalDatabase = Config::get("database.connections.{$originalConnection}.database");

        try {
            // Временно переключаемся на системную БД postgres
            Config::set("database.connections.{$originalConnection}.database", 'postgres');
            DB::purge($originalConnection);
            DB::reconnect($originalConnection);

            // Закрываем все активные соединения к тестовой БД
            DB::select("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ?", [$testDbName]);

            // Проверяем существование базы данных
            $dbExists = DB::select("SELECT 1 FROM pg_database WHERE datname = ?", [$testDbName]);

            if (!empty($dbExists)) {
                $this->info("Удаляем старую тестовую БД '$testDbName'...");
                DB::statement("DROP DATABASE IF EXISTS $testDbName");
            }

            $this->info("Создание тестовой базы данных '$testDbName'...");
            DB::statement("CREATE DATABASE $testDbName");
            $this->info("Тестовая база данных '$testDbName' успешно создана.");

            return Command::SUCCESS;
        } finally {
            // Возвращаем исходные настройки подключения
            Config::set("database.connections.{$originalConnection}.database", $originalDatabase);
            DB::purge($originalConnection);
            DB::reconnect($originalConnection);
        }
    }
}
