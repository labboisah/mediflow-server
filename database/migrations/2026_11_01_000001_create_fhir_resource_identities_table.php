<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fhir_resource_identities', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type', 64);
            $table->string('local_key', 191);
            $table->string('logical_id', 64);
            $table->timestamps();

            $table->unique(['resource_type', 'local_key']);
            $table->unique(['resource_type', 'logical_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fhir_resource_identities');
    }
};
