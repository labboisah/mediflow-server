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
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id');
            $table->foreignId('supplier_id')->nullable();

            $table->string('batch_number');

            $table->decimal('purchase_price',10,2);
            $table->decimal('selling_price',10,2);

            $table->integer('quantity_received');
            $table->integer('quantity_remaining');

            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
    }
};
