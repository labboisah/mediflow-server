<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->date('checked_date');
            $table->foreignId('checked_by')->nullable();
            $table->foreignId('stock_transaction_id')->nullable();
            $table->integer('total_batches_checked')->default(0);
            $table->integer('total_system_quantity')->default(0);
            $table->integer('total_physical_quantity')->default(0);
            $table->integer('total_variance')->default(0);
            $table->decimal('total_variance_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_reconciliations');
    }
};
