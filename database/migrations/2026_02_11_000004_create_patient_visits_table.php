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
        Schema::create('patient_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id');
            $table->dateTime('visit_date');
            $table->string('visit_type'); // e.g., 'Consultation', 'Follow-up', 'Emergency'
            $table->string('flag')->default('Normal');
            $table->text('reason_for_visit')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->string('referred_to')->nullable();
            $table->enum('status', ['Active', 'Transferred', 'Admitted', 'Discharged','Closed'])->default('Active');
            $table->foreignId('created_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_visits');
    }
};
