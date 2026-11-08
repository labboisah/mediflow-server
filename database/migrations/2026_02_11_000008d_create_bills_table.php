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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_visit_id')->nullable();
            $table->foreignId('investigation_request_id')->nullable();
            $table->foreignId('admission_id')->nullable();
            $table->foreignId('walkin_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->string('bill_number')->unique();
            $table->string('service_description');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'paid', 'partial', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('issued_by');
            $table->dateTime('issued_date');
            $table->dateTime('due_date')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('patient_visit_id');
            $table->index('status');
            $table->index('issued_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
