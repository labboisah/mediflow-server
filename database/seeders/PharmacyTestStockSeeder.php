<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineType;
use Illuminate\Database\Seeder;

class PharmacyTestStockSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'type' => 'Tablet',
                'name' => 'Paracetamol',
                'generic_name' => 'Acetaminophen',
                'strength' => '500mg',
                'form' => 'Tablet',
                'manufacturer' => 'Emzor Pharmaceuticals',
                'batches' => [
                    ['batch_number' => 'PCM-TEST-001', 'purchase_price' => 20, 'selling_price' => 50, 'quantity_received' => 300, 'quantity_remaining' => 300, 'months' => 18],
                    ['batch_number' => 'PCM-TEST-002', 'purchase_price' => 22, 'selling_price' => 55, 'quantity_received' => 120, 'quantity_remaining' => 120, 'months' => 8],
                ],
            ],
            [
                'type' => 'Capsule',
                'name' => 'Amoxicillin',
                'generic_name' => 'Amoxicillin',
                'strength' => '500mg',
                'form' => 'Capsule',
                'manufacturer' => 'Fidson Healthcare',
                'batches' => [
                    ['batch_number' => 'AMX-TEST-001', 'purchase_price' => 80, 'selling_price' => 150, 'quantity_received' => 180, 'quantity_remaining' => 180, 'months' => 14],
                ],
            ],
            [
                'type' => 'Tablet',
                'name' => 'Ibuprofen',
                'generic_name' => 'Ibuprofen',
                'strength' => '400mg',
                'form' => 'Tablet',
                'manufacturer' => 'May & Baker',
                'batches' => [
                    ['batch_number' => 'IBU-TEST-001', 'purchase_price' => 35, 'selling_price' => 80, 'quantity_received' => 200, 'quantity_remaining' => 200, 'months' => 16],
                ],
            ],
            [
                'type' => 'Injection',
                'name' => 'Ceftriaxone',
                'generic_name' => 'Ceftriaxone Sodium',
                'strength' => '1g',
                'form' => 'Injection',
                'manufacturer' => 'Juhel Nigeria',
                'batches' => [
                    ['batch_number' => 'CEF-TEST-001', 'purchase_price' => 750, 'selling_price' => 1200, 'quantity_received' => 60, 'quantity_remaining' => 60, 'months' => 12],
                ],
            ],
            [
                'type' => 'Syrup',
                'name' => 'Paracetamol Syrup',
                'generic_name' => 'Acetaminophen',
                'strength' => '120mg/5ml',
                'form' => 'Syrup',
                'manufacturer' => 'Dana Pharmaceuticals',
                'batches' => [
                    ['batch_number' => 'PCS-TEST-001', 'purchase_price' => 450, 'selling_price' => 800, 'quantity_received' => 75, 'quantity_remaining' => 75, 'months' => 10],
                ],
            ],
            [
                'type' => 'Tablet',
                'name' => 'Amlodipine',
                'generic_name' => 'Amlodipine Besylate',
                'strength' => '5mg',
                'form' => 'Tablet',
                'manufacturer' => 'Swiss Pharma Nigeria',
                'batches' => [
                    ['batch_number' => 'AML-TEST-001', 'purchase_price' => 45, 'selling_price' => 100, 'quantity_received' => 150, 'quantity_remaining' => 150, 'months' => 20],
                ],
            ],
        ];

        foreach ($items as $item) {
            $type = MedicineType::firstOrCreate(['name' => $item['type']]);

            $medicine = Medicine::updateOrCreate(
                ['name' => $item['name']],
                [
                    'medicine_type_id' => $type->id,
                    'generic_name' => $item['generic_name'],
                    'strength' => $item['strength'],
                    'form' => $item['form'],
                    'manufacturer' => $item['manufacturer'],
                ]
            );

            foreach ($item['batches'] as $batch) {
                $medicine->batches()->updateOrCreate(
                    ['batch_number' => $batch['batch_number']],
                    [
                        'purchase_price' => $batch['purchase_price'],
                        'selling_price' => $batch['selling_price'],
                        'quantity_received' => $batch['quantity_received'],
                        'quantity_remaining' => $batch['quantity_remaining'],
                        'manufacture_date' => now()->subMonths(2)->toDateString(),
                        'expiry_date' => now()->addMonths($batch['months'])->toDateString(),
                    ]
                );
            }
        }
    }
}
