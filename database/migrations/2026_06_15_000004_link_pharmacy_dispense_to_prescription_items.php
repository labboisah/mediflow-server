<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transaction_items', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transaction_items', 'prescription_item_id')) {
                $table->foreignId('prescription_item_id')->nullable()->after('medicine_batch_id')->constrained('prescription_items')->nullOnDelete();
            }
        });

        Schema::table('pharmacy_dispenses', function (Blueprint $table) {
            if (! Schema::hasColumn('pharmacy_dispenses', 'prescription_item_id')) {
                $table->foreignId('prescription_item_id')->nullable()->after('medicine_batch_id')->constrained('prescription_items')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_dispenses', function (Blueprint $table) {
            if (Schema::hasColumn('pharmacy_dispenses', 'prescription_item_id')) {
                $table->dropConstrainedForeignId('prescription_item_id');
            }
        });

        Schema::table('stock_transaction_items', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transaction_items', 'prescription_item_id')) {
                $table->dropConstrainedForeignId('prescription_item_id');
            }
        });
    }
};
