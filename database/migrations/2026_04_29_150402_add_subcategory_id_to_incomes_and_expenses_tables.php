<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            if (!Schema::hasColumn('incomes', 'subcategory_id')) {
                $table->foreignId('subcategory_id')
                    ->nullable()
                    ->after('category_id')
                    ->constrained('subcategories')
                    ->nullOnDelete();
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'subcategory_id')) {
                $table->foreignId('subcategory_id')
                    ->nullable()
                    ->after('category_id')
                    ->constrained('subcategories')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            if (Schema::hasColumn('incomes', 'subcategory_id')) {
                $table->dropConstrainedForeignId('subcategory_id');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'subcategory_id')) {
                $table->dropConstrainedForeignId('subcategory_id');
            }
        });
    }
};