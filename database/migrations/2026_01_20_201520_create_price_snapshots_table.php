<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_id')->constrained()->cascadeOnDelete();
            $table->date('date'); // por ahora daily
            $table->decimal('close', 14, 4);
            $table->decimal('open', 14, 4)->nullable();
            $table->decimal('high', 14, 4)->nullable();
            $table->decimal('low', 14, 4)->nullable();
            $table->unsignedBigInteger('volume')->nullable();
            $table->timestamps();

            $table->unique(['instrument_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};

