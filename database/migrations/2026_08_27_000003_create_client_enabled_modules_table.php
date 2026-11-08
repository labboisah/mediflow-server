<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_enabled_modules')) {
            return;
        }

        Schema::create('client_enabled_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_license_id')->constrained('client_licenses')->cascadeOnDelete();
            $table->string('module_name')->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamps();

            $table->unique(['client_license_id', 'module_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_enabled_modules');
    }
};
