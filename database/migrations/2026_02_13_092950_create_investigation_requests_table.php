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
        Schema::create('investigation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_visit_id')->nullable();
            $table->foreignId('investigation_id')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->foreignId('performed_by')->nullable();
            $table->foreignId('bill_id')->nullable();
            $table->foreignId('walkin_id')->nullable();
            
            $table->text('clinical_diagnoses');
            $table->date('requested_at');
            $table->date('completed_at')->nullable();
            $table->text('specimen')->nullable();
            $table->string('lab_no')->nullable();
            $table->string('status')->default('Pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investigation_requests');
    }
};
