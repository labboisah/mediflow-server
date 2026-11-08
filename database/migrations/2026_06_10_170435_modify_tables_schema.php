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
        Schema::table('bill_investigations', function (Blueprint $table) {

            $this->addSyncColumns($table);

        });
        Schema::table('file_types', function (Blueprint $table) {

            $this->addSyncColumns($table);

        });

        Schema::table('lgas', function (Blueprint $table) {

            $this->addSyncColumns($table);

        });

        Schema::table('states', function (Blueprint $table) {

            $this->addSyncColumns($table);

        });

        Schema::table('revenues', function (Blueprint $table) {

            $this->addSyncColumns($table);

        });

        Schema::table('revenue_categories', function (Blueprint $table) {

            $this->addSyncColumns($table);

        });

        Schema::table('service_requests', function (Blueprint $table) {

            $this->addSyncColumns($table);

        });

        Schema::table('visit_activities', function (Blueprint $table) {

            $this->addSyncColumns($table);

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_investigations', function (Blueprint $table) {

            $this->removeSyncColumns($table);

        });
        Schema::table('file_types', function (Blueprint $table) {

            $this->removeSyncColumns($table);

        });

        Schema::table('lgas', function (Blueprint $table) {

            $this->removeSyncColumns($table);

        });

        Schema::table('states', function (Blueprint $table) {

            $this->removeSyncColumns($table);

        });

        Schema::table('revenues', function (Blueprint $table) {

            $this->removeSyncColumns($table);

        });
        
        Schema::table('service_requests', function (Blueprint $table) {

            $this->removeSyncColumns($table);

        });
       

        Schema::table('revenue_categories', function (Blueprint $table) {

            $this->removeSyncColumns($table);

        });

        Schema::table('visit_activities', function (Blueprint $table) {

            $this->removeSyncColumns($table);

        });


        
    }

    private function removeSyncColumns(Blueprint $table) {
        $table->dropColumn([
                'sync_uuid',
                'sync_status',
                'sync_origin',
                'sync_updated_at',
                'remote_id'
            ]);
    }
    private function addSyncColumns(Blueprint $table)
    {
        $table->uuid('sync_uuid')
            ->unique()
            ->nullable();

        $table->enum('sync_status', [
            'pending',
            'synced',
            'failed'
        ])->default('pending');

        $table->string('sync_origin')
            ->default('local');

        $table->timestamp('sync_updated_at')
            ->nullable();

        $table->unsignedBigInteger('remote_id')
            ->nullable();
    }
};
