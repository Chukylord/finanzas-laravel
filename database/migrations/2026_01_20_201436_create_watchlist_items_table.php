<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('watchlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained()->cascadeOnDelete();
            $table->enum('horizon', ['short', 'medium', 'long'])->default('medium');
            $table->timestamps();

            $table->unique(['user_id', 'instrument_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlist_items');
    }
};
