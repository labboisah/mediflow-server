<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addVisitColumn('labours', 'patient_id');
        $this->addVisitColumn('deliveries', 'patient_id');
        $this->addVisitColumn('newborns', 'patient_id');
        $this->addVisitColumn('postnatal_examinations', 'patient_id');
        $this->addVisitColumn('child_follow_ups', 'patient_id');

        $this->makeNullableForeign('deliveries', 'labour_id');
        $this->makeNullableForeign('newborns', 'delivery_id');
        $this->makeNullableForeign('postnatal_examinations', 'delivery_id');
        $this->makeNullableForeign('child_follow_ups', 'newborn_id');

        $this->backfillVisitLinks();
    }

    public function down(): void
    {
        $this->makeRequiredForeign('child_follow_ups', 'newborn_id');
        $this->makeRequiredForeign('postnatal_examinations', 'delivery_id');
        $this->makeRequiredForeign('newborns', 'delivery_id');
        $this->makeRequiredForeign('deliveries', 'labour_id');

        foreach (['child_follow_ups', 'postnatal_examinations', 'newborns', 'deliveries', 'labours'] as $tableName) {
            if (Schema::hasColumn($tableName, 'patient_visit_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('patient_visit_id');
                });
            }
        }
    }

    private function addVisitColumn(string $tableName, string $afterColumn): void
    {
        if (Schema::hasColumn($tableName, 'patient_visit_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($afterColumn) {
            $table->foreignId('patient_visit_id')
                ->nullable()
                ->after($afterColumn)
                ->constrained('patient_visits')
                ->nullOnDelete();
        });
    }

    private function makeNullableForeign(string $tableName, string $column): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $column) {
            $table->dropForeign("{$tableName}_{$column}_foreign");
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `{$tableName}` MODIFY `{$column}` BIGINT UNSIGNED NULL");
        }

        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->foreign($column)->references('id')->on(str($column)->beforeLast('_id')->plural()->toString())->nullOnDelete();
        });
    }

    private function makeRequiredForeign(string $tableName, string $column): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $column) {
            $table->dropForeign("{$tableName}_{$column}_foreign");
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `{$tableName}` MODIFY `{$column}` BIGINT UNSIGNED NOT NULL");
        }

        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->foreign($column)->references('id')->on(str($column)->beforeLast('_id')->plural()->toString())->cascadeOnDelete();
        });
    }

    private function backfillVisitLinks(): void
    {
        DB::statement("
            UPDATE labours l
            SET l.patient_visit_id = (
                SELECT pv.id FROM patient_visits pv
                WHERE pv.patient_id = l.patient_id
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, pv.created_at, l.created_at)), pv.id DESC
                LIMIT 1
            )
            WHERE l.patient_visit_id IS NULL
        ");

        DB::statement("
            UPDATE deliveries d
            LEFT JOIN labours l ON l.id = d.labour_id
            SET d.patient_visit_id = COALESCE(l.patient_visit_id, (
                SELECT pv.id FROM patient_visits pv
                WHERE pv.patient_id = d.patient_id
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, pv.created_at, d.created_at)), pv.id DESC
                LIMIT 1
            ))
            WHERE d.patient_visit_id IS NULL
        ");

        DB::statement("
            UPDATE newborns n
            LEFT JOIN deliveries d ON d.id = n.delivery_id
            SET n.patient_visit_id = COALESCE(d.patient_visit_id, (
                SELECT pv.id FROM patient_visits pv
                WHERE pv.patient_id = n.patient_id
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, pv.created_at, n.created_at)), pv.id DESC
                LIMIT 1
            ))
            WHERE n.patient_visit_id IS NULL
        ");

        DB::statement("
            UPDATE postnatal_examinations p
            LEFT JOIN deliveries d ON d.id = p.delivery_id
            SET p.patient_visit_id = COALESCE(d.patient_visit_id, (
                SELECT pv.id FROM patient_visits pv
                WHERE pv.patient_id = p.patient_id
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, pv.created_at, p.created_at)), pv.id DESC
                LIMIT 1
            ))
            WHERE p.patient_visit_id IS NULL
        ");

        DB::statement("
            UPDATE child_follow_ups c
            LEFT JOIN newborns n ON n.id = c.newborn_id
            SET c.patient_visit_id = COALESCE(n.patient_visit_id, (
                SELECT pv.id FROM patient_visits pv
                WHERE pv.patient_id = c.patient_id
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, pv.created_at, c.created_at)), pv.id DESC
                LIMIT 1
            ))
            WHERE c.patient_visit_id IS NULL
        ");
    }
};
