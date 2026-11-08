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
        Schema::create('temporary_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('permission_id');
            $table->foreignId('granted_by');
            $table->text('reason')->nullable();
            $table->dateTime('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'permission_id', 'expires_at'], 'unique_temp_perm');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_permissions');
    }
};
