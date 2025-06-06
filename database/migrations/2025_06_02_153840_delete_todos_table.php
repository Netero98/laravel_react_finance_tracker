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
        Schema::dropIfExists('todos');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }
};
