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
        Schema::create('patient_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id');
            $table->dateTime('referral_date');
            $table->string('referred_to_department');
            $table->text('reason_for_referral');
            $table->enum('status', ['Pending', 'Accepted', 'Completed', 'Rejected'])->default('Pending');
            $table->text('notes')->nullable();
            $table->foreignId('referred_by');
            $table->dateTime('accepted_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_referrals');
    }
};
