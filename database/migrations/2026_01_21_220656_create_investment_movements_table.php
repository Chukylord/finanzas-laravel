<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('investment_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('investment_account_id')
                ->constrained('investment_accounts')
                ->onDelete('cascade');

            $table->date('date');

            // tipos “pro”
            $table->enum('type', ['deposit', 'withdraw', 'profit', 'loss', 'fee', 'adjust'])
                ->default('deposit');

            $table->enum('currency', ['ARS', 'USD'])->default('ARS');

            // siempre positivo, el signo lo define el type
            $table->decimal('amount', 14, 2);

            $table->string('description')->nullable();

            $table->timestamps();

            $table->index(['investment_account_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_movements');
    }
};
