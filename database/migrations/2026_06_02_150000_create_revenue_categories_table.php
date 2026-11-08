<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $categories = [
            'Pharmacy management',
            'SCHS Management',
            'NHIA Management',
            'Guest House services',
            'Partnership Incomes',
            'Special grants',
            'Gifts and donations',
            'Banking loans HFYF',
            'Non-banking loans HFYF'
        ];

        foreach ($categories as $category) {
            \App\Models\RevenueCategory::firstOrCreate(['name' => $category]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_categories');
    }
};
