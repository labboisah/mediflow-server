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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->nullable();
            $table->string('payment_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->foreignId('payment_method_id')->constrained('payment_methods')->default(1);
            $table->string('insurance_provider')->nullable();
            $table->string('reference_number')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('paid_by');
            $table->dateTime('payment_date');
            $table->softDeletes();
            $table->timestamps();

            $table->index('bill_id');
            $table->index('payment_id');
            $table->index('status');
            $table->index('payment_method_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
