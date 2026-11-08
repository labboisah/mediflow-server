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
        Schema::table('labour_progress', function (Blueprint $table) {
            if (!Schema::hasColumn('labour_progress', 'sync_uuid')) {
                $table->uuid('sync_uuid')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('labour_progress', 'sync_status')) {
                $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending')->after('sync_uuid');
            }
            if (!Schema::hasColumn('labour_progress', 'sync_origin')) {
                $table->string('sync_origin')->default('local')->after('sync_status');
            }
            if (!Schema::hasColumn('labour_progress', 'sync_updated_at')) {
                $table->timestamp('sync_updated_at')->nullable()->after('sync_origin');
            }
            if (!Schema::hasColumn('labour_progress', 'remote_id')) {
                $table->unsignedBigInteger('remote_id')->nullable()->after('sync_updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labour_progress', function (Blueprint $table) {
            $table->dropColumnIfExists('sync_uuid');
            $table->dropColumnIfExists('sync_status');
            $table->dropColumnIfExists('sync_origin');
            $table->dropColumnIfExists('sync_updated_at');
            $table->dropColumnIfExists('remote_id');
        });
    }
};
