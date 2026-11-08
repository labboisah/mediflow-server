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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_reference')->unique();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('gross',12,2);
            $table->decimal('discount',12,2);
            $table->decimal('net',12,2);
            $table->decimal('staff_share',12,2);
            $table->decimal('annex_share',12,2);
            $table->decimal('radiologist_share',12,2);
            $table->decimal('radiographer_share',12,2);
            $table->decimal('expenses',12,2);
            $table->decimal('profit',12,2);
            $table->foreignId('user_id')->constrained();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
