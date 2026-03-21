<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            if (!Schema::hasColumn('signals', 'horizon')) {
                $table->enum('horizon', ['short','medium','long'])->default('medium')->after('user_id');
            }
            if (!Schema::hasColumn('signals', 'signal_type')) {
                $table->string('signal_type', 20)->default('mix')->after('horizon'); // sma/ema/rsi/mix
            }
            if (!Schema::hasColumn('signals', 'price')) {
                $table->decimal('price', 14, 4)->nullable()->after('signal_type');
            }
            if (!Schema::hasColumn('signals', 'rsi')) {
                $table->decimal('rsi', 8, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('signals', 'fast_ma')) {
                $table->unsignedSmallInteger('fast_ma')->nullable()->after('rsi');
            }
            if (!Schema::hasColumn('signals', 'slow_ma')) {
                $table->unsignedSmallInteger('slow_ma')->nullable()->after('fast_ma');
            }

            // para evitar duplicados por día/horizonte
            if (!Schema::hasColumn('signals', 'date')) {
                $table->date('date')->after('reason');
            }
        });

        // unique compuesto (instrument+user+horizon+date)
        Schema::table('signals', function (Blueprint $table) {
            // Si ya existe alguno similar, podés omitirlo.
            $table->unique(['user_id', 'instrument_id', 'horizon', 'date'], 'signals_user_inst_horizon_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('signals', function (Blueprint $table) {
            $table->dropUnique('signals_user_inst_horizon_date_unique');

            $cols = ['horizon','signal_type','price','rsi','fast_ma','slow_ma'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('signals', $c)) $table->dropColumn($c);
            }
        });
    }
};
