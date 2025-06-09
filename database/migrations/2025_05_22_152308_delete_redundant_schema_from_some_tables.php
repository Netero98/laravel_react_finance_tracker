<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropColumn('type');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('balance');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['income', 'expense']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->enum('type', ['income', 'expense']);
        });
    }
};
