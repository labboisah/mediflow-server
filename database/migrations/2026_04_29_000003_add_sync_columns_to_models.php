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
        $syncableTables = $this->syncableTables();

        foreach ($syncableTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    if (!Schema::hasColumn($table->getTable(), 'sync_uuid')) {
                        $table->uuid('sync_uuid')->nullable()->unique()->after('id');
                    }
                    if (!Schema::hasColumn($table->getTable(), 'sync_status')) {
                        $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending')->after('sync_uuid');
                    }
                    if (!Schema::hasColumn($table->getTable(), 'sync_origin')) {
                        $table->string('sync_origin')->default('local')->after('sync_status');
                    }
                    if (!Schema::hasColumn($table->getTable(), 'sync_updated_at')) {
                        $table->timestamp('sync_updated_at')->nullable()->after('sync_origin');
                    }
                    if (!Schema::hasColumn($table->getTable(), 'remote_id')) {
                        $table->unsignedBigInteger('remote_id')->nullable()->after('sync_updated_at');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->syncableTables() as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumnIfExists('sync_uuid');
                    $table->dropColumnIfExists('sync_status');
                    $table->dropColumnIfExists('sync_origin');
                    $table->dropColumnIfExists('sync_updated_at');
                    $table->dropColumnIfExists('remote_id');
                });
            }
        }
    }

    private function syncableTables(): array
    {
        return [
            'patients',
            'patient_admissions',
            'patient_visits',
            'payments',
            'bills',
            'prescriptions',
            'vital_signs',
            'observations',
            'antenatal_cares',
            'labours',
            'labour_progresses',
            'labour_progress',
            'deliveries',
            'investigation_requests',
            'investigation_results',
            'fluid_balances',
            'newborn_examinations',
            'drug_charts',
            'discharges',
            'diagnoses',
            'continuations',
            'child_follow_ups',
        ];
    }
};
