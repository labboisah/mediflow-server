<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_results', function (Blueprint $table) {
            $table->index('bill_item_id', 'bill_results_bill_item_id_lookup_index');
            $table->dropUnique('bill_results_bill_item_id_staff_id_unique');
            $table->unique('bill_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('bill_results', function (Blueprint $table) {
            $table->dropUnique('bill_results_bill_item_id_unique');
            $table->unique(['bill_item_id', 'staff_id']);
            $table->dropIndex('bill_results_bill_item_id_lookup_index');
        });
    }
};
