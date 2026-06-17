<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incomes')) {
            Schema::table('incomes', function (Blueprint $table) {
                $table->index(['user_id', 'date'], 'incomes_user_date_idx');
            });
        }

        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->index(['user_id', 'date'], 'expenses_user_date_idx');
                $table->index(['user_id', 'category_id', 'subcategory_id'], 'expenses_user_cat_subcat_idx');
            });
        }

        if (Schema::hasTable('debts')) {
            Schema::table('debts', function (Blueprint $table) {
                $table->index(['user_id', 'active', 'next_due_date'], 'debts_user_active_due_idx');
            });
        }

        if (Schema::hasTable('recurrings')) {
            Schema::table('recurrings', function (Blueprint $table) {
                $table->index(['user_id', 'active', 'next_date'], 'recurrings_user_active_next_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recurrings')) {
            Schema::table('recurrings', function (Blueprint $table) {
                $table->dropIndex('recurrings_user_active_next_idx');
            });
        }

        if (Schema::hasTable('debts')) {
            Schema::table('debts', function (Blueprint $table) {
                $table->dropIndex('debts_user_active_due_idx');
            });
        }

        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropIndex('expenses_user_date_idx');
                $table->dropIndex('expenses_user_cat_subcat_idx');
            });
        }

        if (Schema::hasTable('incomes')) {
            Schema::table('incomes', function (Blueprint $table) {
                $table->dropIndex('incomes_user_date_idx');
            });
        }
    }
};
