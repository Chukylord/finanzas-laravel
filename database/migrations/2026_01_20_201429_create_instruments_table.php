<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20); // GGAL, AAPL, AAPL.BA, BTCUSDT, etc.
            $table->string('name', 120);
            $table->enum('type', ['accion', 'cedear', 'fci', 'crypto'])->default('accion');
            $table->string('market', 40)->nullable(); // BYMA, NASDAQ, NYSE, CRYPTO, etc.
            $table->string('currency', 10)->default('ARS'); // ARS, USD
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['symbol', 'market']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};

