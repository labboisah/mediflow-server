<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->createIfMissing('diagnostic_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('investigation_request_id')->unique('diag_report_req_uniq');
            $table->text('clinical_note')->nullable();
            $table->longText('findings')->nullable();
            $table->longText('impression')->nullable();
            $table->string('status')->default('performed');
            $table->index('status', 'diag_report_status_idx');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });

        $this->createIfMissing('diagnostic_report_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('diagnostic_report_id');
            $table->unsignedInteger('version');
            $table->string('action', 24);
            $table->text('clinical_note')->nullable();
            $table->longText('findings')->nullable();
            $table->longText('impression')->nullable();
            $table->string('status')->default('performed');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->unique(['diagnostic_report_id', 'version'], 'diag_report_revision_uniq');
        });

        $this->createIfMissing('diagnostic_revenue_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('investigation_type_id')->unique('diag_rev_rule_type_uniq');
            $table->decimal('staff_percent', 5, 2)->default(0);
            $table->decimal('radiographer_percent', 5, 2)->default(0);
            $table->decimal('radiologist_percent', 5, 2)->default(0);
            $table->decimal('center_percent', 5, 2)->default(100);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('diagnostic_revenue_distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_investigation_id')->unique('diag_rev_dist_line_uniq');
            $table->unsignedBigInteger('diagnostic_revenue_rule_id')->nullable();
            $table->decimal('base_amount', 12, 2);
            $table->decimal('staff_percent', 5, 2);
            $table->decimal('radiographer_percent', 5, 2);
            $table->decimal('radiologist_percent', 5, 2);
            $table->decimal('center_percent', 5, 2);
            $table->decimal('staff_amount', 12, 2);
            $table->decimal('radiographer_amount', 12, 2);
            $table->decimal('radiologist_amount', 12, 2);
            $table->decimal('center_amount', 12, 2);
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing('diagnostic_import_references', function (Blueprint $table) {
            $table->id();
            $table->string('source_system', 80);
            $table->string('source_table', 80);
            $table->string('source_id', 191);
            $table->string('target_type', 191);
            $table->unsignedBigInteger('target_id');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['source_system', 'source_table', 'source_id'], 'diag_source_identity_uniq');
            $table->index(['target_type', 'target_id'], 'diag_source_target_idx');
        });

        // Supports retry after MySQL created tables but failed while adding a foreign key.
        $this->ensureForeignKey('diagnostic_reports', 'investigation_request_id', 'investigation_requests', 'diag_report_request_fk', 'restrict');
        $this->ensureForeignKey('diagnostic_reports', 'performed_by', 'users', 'diag_report_performer_fk', 'null');
        $this->ensureForeignKey('diagnostic_reports', 'reported_by', 'users', 'diag_report_reporter_fk', 'null');
        $this->ensureForeignKey('diagnostic_report_revisions', 'diagnostic_report_id', 'diagnostic_reports', 'diag_revision_report_fk', 'restrict');
        $this->ensureForeignKey('diagnostic_report_revisions', 'changed_by', 'users', 'diag_revision_author_fk', 'null');
        $this->ensureForeignKey('diagnostic_revenue_rules', 'investigation_type_id', 'investigation_types', 'diag_rev_rule_type_fk', 'restrict');
        $this->ensureForeignKey('diagnostic_revenue_rules', 'updated_by', 'users', 'diag_rev_rule_editor_fk', 'null');
        $this->ensureForeignKey('diagnostic_revenue_distributions', 'bill_investigation_id', 'bill_investigations', 'diag_rev_dist_line_fk', 'restrict');
        $this->ensureForeignKey('diagnostic_revenue_distributions', 'diagnostic_revenue_rule_id', 'diagnostic_revenue_rules', 'diag_rev_dist_rule_fk', 'null');
        $this->ensureForeignKey('diagnostic_revenue_distributions', 'performed_by', 'users', 'diag_rev_dist_performer_fk', 'null');
        $this->ensureForeignKey('diagnostic_revenue_distributions', 'reported_by', 'users', 'diag_rev_dist_reporter_fk', 'null');
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_import_references');
        Schema::dropIfExists('diagnostic_revenue_distributions');
        Schema::dropIfExists('diagnostic_revenue_rules');
        Schema::dropIfExists('diagnostic_report_revisions');
        Schema::dropIfExists('diagnostic_reports');
    }

    private function createIfMissing(string $tableName, Closure $definition): void
    {
        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, $definition);
        }
    }

    private function ensureForeignKey(string $tableName, string $column, string $references, string $name, string $deleteAction): void
    {
        if (collect(Schema::getForeignKeys($tableName))->contains(fn (array $foreignKey) => in_array($column, $foreignKey['columns'] ?? [], true))) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $references, $name, $deleteAction) {
            $foreign = $table->foreign($column, $name)->references('id')->on($references);
            if ($deleteAction === 'null') $foreign->nullOnDelete();
            else $foreign->restrictOnDelete();
        });
    }
};
