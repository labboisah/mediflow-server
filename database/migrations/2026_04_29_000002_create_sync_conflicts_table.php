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
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_uuid');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->enum('conflict_type', ['update_conflict', 'delete_conflict'])->default('update_conflict');
            $table->json('local_data')->nullable();
            $table->json('remote_data')->nullable();
            $table->enum('resolution', ['pending', 'keep_local', 'accept_remote', 'manual_review'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['sync_uuid']);
            $table->index(['resolution']);
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
    }
};
