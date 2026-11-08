<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Route;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = [

        ['name' => 'Oral', 'abbreviation' => 'PO'],
        ['name' => 'Sublingual', 'abbreviation' => 'SL'],
        ['name' => 'Buccal', 'abbreviation' => 'BUC'],
        ['name' => 'Rectal', 'abbreviation' => 'PR'],
        ['name' => 'Nasogastric', 'abbreviation' => 'NG'],
        ['name' => 'Orogastric', 'abbreviation' => 'OG'],

        ['name' => 'Intravenous', 'abbreviation' => 'IV'],
        ['name' => 'Intramuscular', 'abbreviation' => 'IM'],
        ['name' => 'Subcutaneous', 'abbreviation' => 'SC'],
        ['name' => 'Intradermal', 'abbreviation' => 'ID'],
        ['name' => 'Intraosseous', 'abbreviation' => 'IO'],
        ['name' => 'Intrathecal', 'abbreviation' => 'IT'],
        ['name' => 'Intra-arterial', 'abbreviation' => 'IA'],
        ['name' => 'Intraperitoneal', 'abbreviation' => 'IP'],
        ['name' => 'Intracardiac', 'abbreviation' => 'IC'],

        ['name' => 'Inhalation', 'abbreviation' => 'INH'],
        ['name' => 'Nebulization', 'abbreviation' => 'NEB'],

        ['name' => 'Topical', 'abbreviation' => 'TOP'],
        ['name' => 'Transdermal', 'abbreviation' => 'TD'],
        ['name' => 'Ophthalmic', 'abbreviation' => 'OPH'],
        ['name' => 'Otic', 'abbreviation' => 'OTIC'],
        ['name' => 'Nasal', 'abbreviation' => 'NAS'],

        ['name' => 'Vaginal', 'abbreviation' => 'PV'],
        ['name' => 'Urethral', 'abbreviation' => 'UR'],

        ['name' => 'Epidural', 'abbreviation' => 'EPID'],
        ['name' => 'Intra-articular', 'abbreviation' => 'IAJ'],
        ['name' => 'Intravitreal', 'abbreviation' => 'IVT'],
        ['name' => 'Intravesical', 'abbreviation' => 'IVS']

        ];

        foreach($routes as $route){
            Route::firstOrCreate($route);
        }
    }
}
