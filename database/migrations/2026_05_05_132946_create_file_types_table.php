<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FileType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('price', 10, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $files = [
            ['name' => 'Opening Personal File', 'price' => 2000.00],
            ['name' => 'Opening Family File', 'price' => 3000.00],
        ];

        foreach ($files as $file) {
            FileType::create($file);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_types');
    }
};
