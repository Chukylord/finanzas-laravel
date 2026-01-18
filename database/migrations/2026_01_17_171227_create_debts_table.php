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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->foreignId('category_id')->constrained()->onDelete('restrict'); // categoría de egreso

            $table->string('name'); // "Préstamo Banco", "Celular en cuotas"
            $table->enum('type', ['loan', 'card_installment'])->default('loan');

            $table->decimal('total_amount', 12, 2);
            $table->unsignedSmallInteger('installments_total');
            $table->unsignedSmallInteger('installments_paid')->default(0);

            $table->decimal('installment_amount', 12, 2); // cuota mensual
            $table->unsignedTinyInteger('day_of_month')->default(1);
            $table->date('next_due_date');

            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
