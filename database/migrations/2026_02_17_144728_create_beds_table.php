<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Ward;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id');
            $table->string('bed_no');
            $table->string('status')->default('vacant');
            $table->timestamps();
        });

        $wards = [
            ['name' => 'General Ward', 'capacity' => 20],
            ['name' => 'Maternity Ward', 'capacity' => 15],
            ['name' => 'Pediatric Ward', 'capacity' => 10],
            ['name' => 'ICU', 'capacity' => 5],
            ['name' => 'Male Surgical Ward', 'capacity' => 10],
            ['name' => 'Female Surgical Ward', 'capacity' => 10],
            ['name'=>'Labour Room', 'capacity'=>10]
        ];

        foreach($wards as $ward){
            $ward['price'] = 2000; // Set a default price for all wards, can be adjusted as needed
            $w = Ward::firstOrCreate($ward);
            for($capacity = 1; $capacity <= $ward['capacity']; $capacity++){
                $w->beds()->create(['bed_no'=>$this->format($capacity)]);
            }
        }

    }

    private function format($number) {
        if($number <= 9){
            $number = '0'.$number;
        }
        return $number;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
