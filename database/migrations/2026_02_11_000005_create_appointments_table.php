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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->enum('status', ['Scheduled', 'Completed', 'Cancelled', 'No-show'])->default('Scheduled');
            $table->text('notes')->nullable();
            $table->foreignId('scheduled_by');
            $table->dateTime('cancelled_date')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
