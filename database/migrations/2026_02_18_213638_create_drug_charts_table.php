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
        Schema::create('drug_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_item_id');
            $table->foreignId('medicine_id')->nullable();
            $table->foreignId('route_id')->nullable();
            $table->string('dosage')->nullable();
            $table->string('comment')->nullable();
            $table->string('time');
            $table->foreignId('dispensed_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_charts');
    }
};
