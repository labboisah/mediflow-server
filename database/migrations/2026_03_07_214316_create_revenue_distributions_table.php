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
        Schema::create('revenue_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_item_id')->constrained()->cascadeOnDelete();

            $table->decimal('radiologist_amount',12,2)->default(0);
            $table->decimal('radiographer_amount',12,2)->default(0);
            $table->decimal('staff_amount',12,2)->default(0);
            $table->decimal('annex_amount',12,2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_distributions');
    }
};
