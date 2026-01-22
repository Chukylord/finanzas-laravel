<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('usd_ars', 12, 4); // ejemplo: 1200.50
            $table->string('source')->nullable(); // "manual", "iol", "tradingview", etc
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
