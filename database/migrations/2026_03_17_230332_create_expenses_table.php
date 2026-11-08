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
        if (! Schema::hasTable('expenses')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('expenses', 'expense_category_id')) {
                $table->foreignId('expense_category_id')->nullable()->after('department_id');
            }

            if (! Schema::hasColumn('expenses', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            foreach (['department_id', 'expense_category_id', 'created_by'] as $column) {
                if (Schema::hasColumn('expenses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
