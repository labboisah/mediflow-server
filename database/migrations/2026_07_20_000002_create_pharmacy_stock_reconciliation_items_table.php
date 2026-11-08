<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_stock_reconciliation_id');
            $table->foreignId('medicine_batch_id');
            $table->foreignId('medicine_id')->nullable();
            $table->string('medicine_name')->nullable();
            $table->string('batch_number')->nullable();
            $table->integer('system_quantity');
            $table->integer('physical_quantity');
            $table->integer('variance');
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->decimal('variance_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('pharmacy_stock_reconciliation_id', 'psri_reconciliation_index');
            $table->index('medicine_batch_id', 'psri_batch_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_reconciliation_items');
    }
};
