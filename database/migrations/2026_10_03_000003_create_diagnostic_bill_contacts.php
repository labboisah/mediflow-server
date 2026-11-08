<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('diagnostic_bill_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('source_system', 80)->default('medistop');
            $table->string('source_patient_id', 191)->nullable()->index();
            $table->string('patient_phone')->nullable();
            $table->string('hospital_number')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_bill_contacts');
    }
};
