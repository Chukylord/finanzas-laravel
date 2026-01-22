<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signal_rule_id')->nullable()->constrained()->nullOnDelete();

            $table->date('date');
            $table->enum('action', ['buy', 'sell', 'hold'])->default('hold');
            $table->unsignedTinyInteger('score')->default(50); // 0..100
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
