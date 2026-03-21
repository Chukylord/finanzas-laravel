<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('price_snapshots', function (Blueprint $table) {
            $table->string('currency', 30)->nullable()->after('close'); // peso_Argentino, dolar_Estadounidense, etc
            $table->decimal('change_pct', 8, 2)->nullable()->after('currency'); // variacion
            $table->timestamp('fetched_at')->nullable()->after('change_pct');
        });
    }

    public function down(): void
    {
        Schema::table('price_snapshots', function (Blueprint $table) {
            $table->dropColumn(['currency', 'change_pct', 'fetched_at']);
        });
    }
};
