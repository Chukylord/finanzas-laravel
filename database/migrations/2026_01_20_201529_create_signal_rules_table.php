<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signal_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->enum('horizon', ['short', 'medium', 'long'])->default('medium');

            // para arrancar: estrategia simple por medias
            $table->unsignedSmallInteger('fast_ma')->default(20);
            $table->unsignedSmallInteger('slow_ma')->default(50);

            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_rules');
    }
};
