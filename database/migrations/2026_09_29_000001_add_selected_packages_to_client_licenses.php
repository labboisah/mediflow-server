<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_licenses', fn (Blueprint $t) => $t->json('selected_packages')->nullable());
        DB::table('client_licenses')->orderBy('id')->each(function ($license) {
            $plan = config("mediflow_modules.legacy_plan_aliases.{$license->plan}", $license->plan);
            DB::table('client_licenses')->where('id', $license->id)->update(['selected_packages' => json_encode([$plan])]);
        });
    }

    public function down(): void
    {
        Schema::table('client_licenses', fn (Blueprint $t) => $t->dropColumn('selected_packages'));
    }
};
