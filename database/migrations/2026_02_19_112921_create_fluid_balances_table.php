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
        Schema::create('fluid_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id');
            $table->foreignId('recorded_by');
            $table->timestamp('date');
            $table->string('time');
            $table->string('type_in')->nullable();
            $table->string('tube_in')->nullable();
            $table->string('oral')->nullable();
            $table->string('iv')->nullable();
            // out
            $table->string('urine')->nullable();
            $table->string('tube_out')->nullable();
            $table->string('faces')->nullable();
            $table->string('type_out')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fluid_balances');
    }
};
