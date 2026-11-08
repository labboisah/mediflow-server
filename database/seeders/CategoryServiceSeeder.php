<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Service;
use App\Models\RevenueSharingRule;

class CategoryServiceSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Create Categories
        |--------------------------------------------------------------------------
        */

        $xrays = Category::create(['name' => 'X-Rays Services']);
        $ultrasound = Category::create(['name' => 'Ultra Sound']);
        $specialScan = Category::create(['name' => 'Special Scan']);
        $dopplerScan = Category::create(['name' => 'Doppler Scan']);
        $echo = Category::create(['name' => 'Echocardiography (ECHO)']);
        $ecg = Category::create(['name' => 'Electrocardiography (ECG)']);
        $lab = Category::create(['name' => 'Laboratory Services']);

        $xrays->revenueSharingRule()->create([
            'radiologist_percent' => 25,
            'radiographer_percent' => 25,
            'annex_percent' => 50
        ]);

        $ultrasound->revenueSharingRule()->create([
            'radiologist_percent' => 0,
            'radiographer_percent' => 0,
            'annex_percent' => 60,
            'staff_percent' => 40
        ]);

        $specialScan->revenueSharingRule()->create([
            'radiologist_percent' => 0,
            'radiographer_percent' => 0,
            'annex_percent' => 60,
            'staff_percent' => 40
        ]);


        $dopplerScan->revenueSharingRule()->create([
            'radiologist_percent' => 0,
            'radiographer_percent' => 0,
            'annex_percent' => 60,
            'staff_percent' => 40
        ]);

        $echo->revenueSharingRule()->create([
            'radiologist_percent' => 0,
            'radiographer_percent' => 0,
            'annex_percent' => 60,
            'staff_percent' => 40
        ]);

        $ecg->revenueSharingRule()->create([
            'radiologist_percent' => 0,
            'radiographer_percent' => 0,
            'annex_percent' => 60,
            'staff_percent' => 40
        ]);

        $lab->revenueSharingRule()->create([
            'radiologist_percent' => 0,
            'radiographer_percent' => 0,
            'annex_percent' => 60,
            'staff_percent' => 40
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Radiology - Ultrasound Routine (₦4,000)
        |--------------------------------------------------------------------------
        */

        $ultrasoundServices = [
            'Obstetric Scan',
            'Abdominopelvic Scan',
            'Pelvic Scan',
        ];

        foreach ($ultrasoundServices as $service) {
            Service::create([
                'category_id' => $ultrasound->id,
                'name' => $service . ' (Routine)',
                'price' => 4000
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Radiology - Special Scan (₦6,000)
        |--------------------------------------------------------------------------
        */

        $specialScanServices = [
            'BPP & FWE',
            'Lesional Scan',
            'Scrotal Scan',
            'HVS',
            'Ocular Scan',
            'Thyroid Scan',
            'Chest Scan',
            'Renal Scan',
            'Hepatobiliary Scan',
            'Musculoskeletal Scan',
            'Breast USS',
            'Prostate Scan',
            'TVS',
        ];

        foreach ($specialScanServices as $service) {
            Service::create([
                'category_id' => $specialScan->id,
                'name' => $service . ' (Special)',
                'price' => 6000
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Radiology - Doppler Scan (₦15,000)
        |--------------------------------------------------------------------------
        */

        $dopplerScanServices = [
            'All Doppler',
            'LLL Doppler',
            'RLL Doppler',
            'LUE Doppler',
            'Bilateral LL Doppler',
            'Carotid Doppler',
            'Penile Doppler',
            'Scrotal Doppler',
            'Hepatic Doppler',
            'Renal Doppler',
            'Transcranial Doppler',
        ];

        foreach ($dopplerScanServices as $service) {
            Service::create([
                'category_id' => $dopplerScan->id,
                'name' => $service . ' (Doppler)',
                'price' => 15000
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Radiology - X-Ray (Assuming ₦5,000 default)
        |--------------------------------------------------------------------------
        */

        $xrayServices = [
            'CXR',
            'Skull',
            'TMJ',
            'Post Nasal Space',
            'Paranasal Sinuses',
            'Cervical',
            'Soft Tissue Neck',
            'Abdomen',
            'Abdomen (Erect & Supine)',
            'KUB',
            'Lumbosacral',
            'Thoracic',
            'Whole Spine',
            'Pelvis',
            'Right Limb',
            'Left Limb',
            'Arm',
            'Left Leg',
            'Ankle',
            'Foot',
        ];

        foreach ($xrayServices as $service) {
            Service::create([
                'category_id' => $xrays->id,
                'name' => $service . ' (X-Ray)',
                'price' => 5000
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Other Main Services (No price yet — can update later)
        |--------------------------------------------------------------------------
        */

        Service::create([
            'category_id' => $echo->id,
            'name' => 'Echocardiography Service',
            'price' => 0
        ]);

        Service::create([
            'category_id' => $ecg->id,
            'name' => 'Electrocardiography Service',
            'price' => 0
        ]);

        Service::create([
            'category_id' => $lab->id,
            'name' => 'Laboratory General Services',
            'price' => 0
        ]);
    }
}