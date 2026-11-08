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
        Schema::create('continuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_visit_id');
            $table->foreignId('written_by');
            $table->text('note');
            $table->timestamp('date')->default(now());
            $table->string('time')->default(date('h:m:s A'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('continuations');
    }
};
