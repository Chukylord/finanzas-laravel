<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('investment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('name'); // "Ahorros fijos", "IOL", "Cripto", etc
            $table->enum('default_currency', ['ARS', 'USD'])->default('ARS');

            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_accounts');
    }
};
