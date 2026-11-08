<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->string('patient_name')->nullable();
        });
        Schema::create('pharmacy_service_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('stock_transactions');
            $table->foreignId('pharmacy_service_id')->constrained('pharmacy_services');
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_service_items');
        Schema::table('stock_transactions', fn (Blueprint $table) => $table->dropColumn('patient_name'));
        Schema::dropIfExists('pharmacy_services');
    }
};
