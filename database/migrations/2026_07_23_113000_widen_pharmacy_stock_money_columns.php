<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->decimal('total_amount', 15, 2)->change();
        });

        Schema::table('stock_transaction_items', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->change();
            $table->decimal('subtotal', 15, 2)->change();
        });

        Schema::table('medicine_batches', function (Blueprint $table) {
            $table->decimal('purchase_price', 15, 2)->change();
            $table->decimal('selling_price', 15, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('medicine_batches', function (Blueprint $table) {
            $table->decimal('purchase_price', 10, 2)->change();
            $table->decimal('selling_price', 10, 2)->change();
        });

        Schema::table('stock_transaction_items', function (Blueprint $table) {
            $table->decimal('price', 8, 2)->change();
            $table->decimal('subtotal', 8, 2)->change();
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->decimal('total_amount', 8, 2)->change();
        });
    }
};
