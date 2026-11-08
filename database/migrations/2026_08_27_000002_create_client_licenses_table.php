<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_licenses')) {
            return;
        }

        Schema::create('client_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('plan')->index();
            $table->string('license_key')->nullable()->unique();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_licenses');
    }
};
