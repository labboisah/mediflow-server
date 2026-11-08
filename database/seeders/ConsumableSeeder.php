<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;

class ConsumableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $consumables = [

            ['name' => 'PSA'],
            ['name' => 'Triglyceride'],
            ['name' => 'EDTA Vacuum'],
            ['name' => 'Plain Tube Vacuum'],
            ['name' => 'Lithium Heparin'],
            ['name' => 'HCV'],
            ['name' => 'HBsAg'],
            ['name' => 'Widal Kit'],
            ['name' => 'Bilirubin T & D'],
            ['name' => 'MRDT'],
            ['name' => 'Potassium'],
            ['name' => 'H. Pylori'],
            ['name' => 'Chloride'],
            ['name' => 'Plain Capillary Tube'],
            ['name' => 'Heparinize Capillary'],
            ['name' => 'Hand Glove'],
            ['name' => 'Urinalysis Strip'],
            ['name' => '450ml Blood Bag'],
            ['name' => 'Blood Giving Set'],
            ['name' => '5ml Syringe'],
            ['name' => '2ml Syringe'],
            ['name' => 'A4 Paper'],
            ['name' => 'VDRL'],
            ['name' => 'Determine'],
            ['name' => 'Pregnancy Test Strip'],
            ['name' => 'Anti Sera A'],
            ['name' => 'Anti Sera B'],
            ['name' => 'Anti Sera D'],
            ['name' => 'ALT'],
            ['name' => 'Yellow Pipette Tips'],
            ['name' => 'Cotton Wool'],
            ['name' => 'Hand Wash'],
            ['name' => 'Hard Cover Note'],
            ['name' => 'Air Freshener'],
            ['name' => 'Face Mask'],
            ['name' => 'Mantoux'],
            ['name' => 'Nutrient Agar'],
            ['name' => 'Urea B'],
            ['name' => 'Calcium'],
            ['name' => 'Uric Acid'],
            ['name' => 'Thermal Paper'],
            ['name' => 'Probe Cleanser'],
            ['name' => 'Elisa Rapid'],
            ['name' => 'Hypochlorite'],
            ['name' => 'Test Tube Brush'],
            ['name' => 'Constricted Cuvette'],
            ['name' => 'Urine Bottle Foreign'],
            ['name' => 'ESR Tube'],
            ['name' => 'Masking Tape'],
            ['name' => 'Cryovial'],
            ['name' => 'M 30 Diluent'],
            ['name' => 'On Point Strip']

        ];
        $department = Department::find(2);
        foreach ($consumables as $item) {

            $department->consumables()->firstOrCreate([
                'name' => $item['name']
            ]);

        }
    }
}
