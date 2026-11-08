<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\InvestigationType;
use App\Models\Investigation;
use App\Models\Parameter;

class InvestigationSeeder extends Seeder
{
    public function run()
    {

        /*
        |-----------------------------------------------------
        | DEPARTMENTS
        |-----------------------------------------------------
        */

        $radiology = Department::create(['name' => 'Radiology']);
        $laboratory = Department::create(['name' => 'Laboratory']);
        $pharmacy = Department::create(['name' => 'Pharmacy']);
        $record = Department::create(['name' => 'Record']);
        


        /*
        |-----------------------------------------------------
        | RADIOLOGY TYPES
        |-----------------------------------------------------
        */

        $xray = InvestigationType::create([
            'department_id' => $radiology->id,
            'name' => 'X-Ray'
        ]);

        $contrast = InvestigationType::create([
            'department_id' => $radiology->id,
            'name' => 'Contrast Studies'
        ]);

        $ultrasound = InvestigationType::create([
            'department_id' => $radiology->id,
            'name' => 'Ultrasound'
        ]);

        $cardiology = InvestigationType::create([
            'department_id' => $radiology->id,
            'name' => 'Cardiology'
        ]);


        /*
        |-----------------------------------------------------
        | LAB TYPES
        |-----------------------------------------------------
        */

        $chemical = InvestigationType::create([
            'department_id' => $laboratory->id,
            'name' => 'Chemical Pathology'
        ]);

        $haematology = InvestigationType::create([
            'department_id' => $laboratory->id,
            'name' => 'Haematology'
        ]);

        $microbiology = InvestigationType::create([
            'department_id' => $laboratory->id,
            'name' => 'Microbiology'
        ]);


        /*
        |-----------------------------------------------------
        | RADIOLOGY - X RAY
        |-----------------------------------------------------
        */

        $xrays = [

[
'name' => 'Chest X-Ray AP',
'price' => 5000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Chest AP & LAT',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Abdominal X-Ray (KUB)',
'price' => 6000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Plain Abdominal X-Ray (Erect & Supine)',
'price' => 10000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Foot AP & LAT',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Ankle AP & LAT',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Leg/Tibia and Fibula',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Femur AP & LAT',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Knee AP & LAT',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Hip AP & LAT/Both',
'price' => 10000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Hand AP & LAT',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Wrist AP & LAT',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Forearm / Radius & Ulna',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Elbow Joint',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Humerus AP & LAT',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Shoulder Joint AP',
'price' => 8000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Skull X-Ray',
'price' => 6000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

];

foreach($xrays as $x){

    $investigation = Investigation::create([
        'investigation_type_id' => $xray->id,
        'name' => $x['name'],
        'price' => $x['price']
    ]);

    foreach($x['parameters'] as $param){

        
    Parameter::create([
            'investigation_id' => $investigation->id,
            'name' => $param['name'],
            'unit' => $param['unit'],
            'reference_range' => $param['reference_range']
        ]);

    }

}

        /*
        |-----------------------------------------------------
        | RADIOLOGY - CONTRAST
        |-----------------------------------------------------
        */

        $contrastStudies = [

[
'name' => 'IVU',
'price' => 30000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'HSG',
'price' => 25000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'RUCG',
'price' => 20000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'MUCG',
'price' => 25000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'RUCG/MUCG',
'price' => 35000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Sinography',
'price' => 15000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Genitography',
'price' => 25000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Barium Enema',
'price' => 35000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Barium Meal',
'price' => 25000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Barium Swallow',
'price' => 15000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Barium Swallow/Meal',
'price' => 30000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Sialography',
'price' => 25000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Fistulography',
'price' => 10000,
'parameters' => [
['name'=>'Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
]

];

foreach($contrastStudies as $c){

    $investigation = Investigation::create([
        'investigation_type_id'=>$contrast->id,
        'name'=>$c['name'],
        'price'=>$c['price']
    ]);

    foreach($c['parameters'] as $param){

        
    Parameter::create([
            'investigation_id'=>$investigation->id,
            'name'=>$param['name'],
            'unit'=>$param['unit'],
            'reference_range'=>$param['reference_range']
        ]);

    }

}



        /*
        |-----------------------------------------------------
        | ULTRASOUND
        |-----------------------------------------------------
        */

        $ultrasounds = [

[
'name' => 'Abdominal USS',
'price' => 3000,
'parameters' => [
['name'=>'Liver','unit'=>null,'reference_range'=>null],
['name'=>'Gallbladder','unit'=>null,'reference_range'=>null],
['name'=>'Pancreas','unit'=>null,'reference_range'=>null],
['name'=>'Kidneys','unit'=>null,'reference_range'=>null],
['name'=>'Spleen','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Abdominal Pelvic USS',
'price' => 3000,
'parameters' => [
['name'=>'Abdominal Findings','unit'=>null,'reference_range'=>null],
['name'=>'Pelvic Findings','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Pelvic USS',
'price' => 3000,
'parameters' => [
['name'=>'Uterus','unit'=>null,'reference_range'=>null],
['name'=>'Ovaries','unit'=>null,'reference_range'=>null],
['name'=>'Adnexa','unit'=>null,'reference_range'=>null],
['name'=>'Pouch of Douglas','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Obstetric USS',
'price' => 3000,
'parameters' => [
['name'=>'Gestational Age','unit'=>'weeks','reference_range'=>null],
['name'=>'Fetal Heart Rate','unit'=>'bpm','reference_range'=>'120-160'],
['name'=>'Placenta','unit'=>null,'reference_range'=>null],
['name'=>'Amniotic Fluid','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Lessional USS',
'price' => 4500,
'parameters' => [
['name'=>'Location','unit'=>null,'reference_range'=>null],
['name'=>'Size','unit'=>'cm','reference_range'=>null],
['name'=>'Echotexture','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Knee USS',
'price' => 4500,
'parameters' => [
['name'=>'Joint Effusion','unit'=>null,'reference_range'=>null],
['name'=>'Ligaments','unit'=>null,'reference_range'=>null],
['name'=>'Soft Tissue','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Arm USS',
'price' => 4500,
'parameters' => [
['name'=>'Muscle','unit'=>null,'reference_range'=>null],
['name'=>'Tendon','unit'=>null,'reference_range'=>null],
['name'=>'Soft Tissue','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Thyroid USS',
'price' => 4500,
'parameters' => [
['name'=>'Right Lobe Size','unit'=>'cm','reference_range'=>null],
['name'=>'Left Lobe Size','unit'=>'cm','reference_range'=>null],
['name'=>'Isthmus','unit'=>'mm','reference_range'=>null],
['name'=>'Nodules','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Neck USS',
'price' => 4500,
'parameters' => [
['name'=>'Lymph Nodes','unit'=>null,'reference_range'=>null],
['name'=>'Soft Tissue','unit'=>null,'reference_range'=>null],
['name'=>'Masses','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Transfuntal USS',
'price' => 4500,
'parameters' => [
['name'=>'Brain Structures','unit'=>null,'reference_range'=>null],
['name'=>'Ventricles','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'TVS USS',
'price' => 4500,
'parameters' => [
['name'=>'Uterus','unit'=>null,'reference_range'=>null],
['name'=>'Endometrium Thickness','unit'=>'mm','reference_range'=>null],
['name'=>'Ovaries','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Scrotal USS',
'price' => 4500,
'parameters' => [
['name'=>'Testes','unit'=>null,'reference_range'=>null],
['name'=>'Epididymis','unit'=>null,'reference_range'=>null],
['name'=>'Hydrocele','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Renal USS',
'price' => 4500,
'parameters' => [
['name'=>'Right Kidney','unit'=>null,'reference_range'=>null],
['name'=>'Left Kidney','unit'=>null,'reference_range'=>null],
['name'=>'Corticomedullary Differentiation','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Doppler USS',
'price' => 15000,
'parameters' => [
['name'=>'Blood Flow','unit'=>null,'reference_range'=>null],
['name'=>'Velocity','unit'=>'cm/s','reference_range'=>null],
['name'=>'Waveform','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
],

[
'name' => 'Sono HSG',
'price' => 15000,
'parameters' => [
['name'=>'Uterine Cavity','unit'=>null,'reference_range'=>null],
['name'=>'Fallopian Tubes','unit'=>null,'reference_range'=>null],
['name'=>'Spillage','unit'=>null,'reference_range'=>null],
['name'=>'Impression','unit'=>null,'reference_range'=>null],
]
]

];

foreach($ultrasounds as $u){

    $investigation = Investigation::create([
        'investigation_type_id'=>$ultrasound->id,
        'name'=>$u['name'],
        'price'=>$u['price']
    ]);

    foreach($u['parameters'] as $param){

        
    Parameter::create([
            'investigation_id'=>$investigation->id,
            'name'=>$param['name'],
            'unit'=>$param['unit'],
            'reference_range'=>$param['reference_range']
        ]);

    }

}



        /*
        |-----------------------------------------------------
        | CARDIOLOGY
        |-----------------------------------------------------
        */

        Investigation::create([
            'investigation_type_id'=>$cardiology->id,
            'name'=>'ECG',
            'price'=>4000
        ]);

        Investigation::create([
            'investigation_type_id'=>$cardiology->id,
            'name'=>'ECHO',
            'price'=>15000
        ]);



        /*
        |-----------------------------------------------------
        | CHEMICAL PATHOLOGY
        |-----------------------------------------------------
        */

        $chemicalTests = [

[
'name'=>'Fasting Blood Sugar',
'price'=>1000,
'parameters'=>[
['name'=>'Glucose','unit'=>'mmol/L','reference_range'=>'3.9 - 5.5']
]
],

[
'name'=>'Random Blood Sugar',
'price'=>1000,
'parameters'=>[
['name'=>'Glucose','unit'=>'mmol/L','reference_range'=>'< 11.1']
]
],

[
'name'=>'2 Hours Postprandial',
'price'=>1500,
'parameters'=>[
['name'=>'Glucose','unit'=>'mmol/L','reference_range'=>'< 7.8']
]
],

[
'name'=>'EUCr',
'price'=>4000,
'parameters'=>[
['name'=>'Urea','unit'=>'mmol/L','reference_range'=>'2.5 - 7.1'],
['name'=>'Creatinine','unit'=>'µmol/L','reference_range'=>'62 - 106'],
['name'=>'Sodium','unit'=>'mmol/L','reference_range'=>'135 - 145'],
['name'=>'Potassium','unit'=>'mmol/L','reference_range'=>'3.5 - 5.0'],
['name'=>'Chloride','unit'=>'mmol/L','reference_range'=>'98 - 106'],
['name'=>'Bicarbonate','unit'=>'mmol/L','reference_range'=>'22 - 29']
]
],

[
'name'=>'LFT',
'price'=>5500,
'parameters'=>[
['name'=>'ALT','unit'=>'U/L','reference_range'=>'7 - 56'],
['name'=>'AST','unit'=>'U/L','reference_range'=>'10 - 40'],
['name'=>'ALP','unit'=>'U/L','reference_range'=>'44 - 147'],
['name'=>'Total Bilirubin','unit'=>'µmol/L','reference_range'=>'5 - 21'],
['name'=>'Direct Bilirubin','unit'=>'µmol/L','reference_range'=>'0 - 5'],
['name'=>'Albumin','unit'=>'g/L','reference_range'=>'35 - 50']
]
],

[
'name'=>'OGTT',
'price'=>5000,
'parameters'=>[
['name'=>'Fasting Glucose','unit'=>'mmol/L','reference_range'=>'3.9 - 5.5'],
['name'=>'1 Hour Glucose','unit'=>'mmol/L','reference_range'=>'< 10'],
['name'=>'2 Hour Glucose','unit'=>'mmol/L','reference_range'=>'< 7.8']
]
],

[
'name'=>'Uric Acid',
'price'=>3500,
'parameters'=>[
['name'=>'Uric Acid','unit'=>'µmol/L','reference_range'=>'200 - 420']
]
],

[
'name'=>'Fasting Lipid Profile',
'price'=>6000,
'parameters'=>[
['name'=>'Total Cholesterol','unit'=>'mmol/L','reference_range'=>'< 5.2'],
['name'=>'HDL','unit'=>'mmol/L','reference_range'=>'> 1.0'],
['name'=>'LDL','unit'=>'mmol/L','reference_range'=>'< 3.4'],
['name'=>'Triglycerides','unit'=>'mmol/L','reference_range'=>'< 1.7']
]
],

[
'name'=>'Calcium',
'price'=>4000,
'parameters'=>[
['name'=>'Calcium','unit'=>'mmol/L','reference_range'=>'2.1 - 2.6']
]
],

[
'name'=>'Magnesium',
'price'=>3000,
'parameters'=>[
['name'=>'Magnesium','unit'=>'mmol/L','reference_range'=>'0.7 - 1.0']
]
],

[
'name'=>'Phosphorous',
'price'=>3000,
'parameters'=>[
['name'=>'Phosphate','unit'=>'mmol/L','reference_range'=>'0.8 - 1.5']
]
],

[
'name'=>'Bilirubin Total',
'price'=>2000,
'parameters'=>[
['name'=>'Total Bilirubin','unit'=>'µmol/L','reference_range'=>'5 - 21']
]
],

[
'name'=>'Bilirubin Direct',
'price'=>2000,
'parameters'=>[
['name'=>'Direct Bilirubin','unit'=>'µmol/L','reference_range'=>'0 - 5']
]
],

[
'name'=>'Urinalysis',
'price'=>1000,
'parameters'=>[
['name'=>'Color','unit'=>null,'reference_range'=>'Yellow'],
['name'=>'Appearance','unit'=>null,'reference_range'=>'Clear'],
['name'=>'pH','unit'=>null,'reference_range'=>'4.5 - 8'],
['name'=>'Protein','unit'=>null,'reference_range'=>'Negative'],
['name'=>'Glucose','unit'=>null,'reference_range'=>'Negative'],
['name'=>'Ketones','unit'=>null,'reference_range'=>'Negative']
]
],

[
'name'=>'Pregnancy Test',
'price'=>1200,
'parameters'=>[
['name'=>'HCG','unit'=>null,'reference_range'=>'Negative']
]
],

[
'name'=>'Electrolytes',
'price'=>3000,
'parameters'=>[
['name'=>'Sodium','unit'=>'mmol/L','reference_range'=>'135 - 145'],
['name'=>'Potassium','unit'=>'mmol/L','reference_range'=>'3.5 - 5.0'],
['name'=>'Chloride','unit'=>'mmol/L','reference_range'=>'98 - 106'],
['name'=>'Bicarbonate','unit'=>'mmol/L','reference_range'=>'22 - 29']
]
],

[
'name'=>'Potassium',
'price'=>2000,
'parameters'=>[
['name'=>'Potassium','unit'=>'mmol/L','reference_range'=>'3.5 - 5.0']
]
],

[
'name'=>'PSA',
'price'=>6500,
'parameters'=>[
['name'=>'PSA','unit'=>'ng/mL','reference_range'=>'0 - 4']
]
],

[
'name'=>'HBa1c',
'price'=>7000,
'parameters'=>[
['name'=>'HbA1c','unit'=>'%','reference_range'=>'4 - 5.6']
]
],

[
'name'=>'T3',
'price'=>5000,
'parameters'=>[
['name'=>'T3','unit'=>'ng/dL','reference_range'=>'80 - 200']
]
],

[
'name'=>'T4',
'price'=>5000,
'parameters'=>[
['name'=>'T4','unit'=>'µg/dL','reference_range'=>'5 - 12']
]
],

[
'name'=>'TSH',
'price'=>5000,
'parameters'=>[
['name'=>'TSH','unit'=>'mIU/L','reference_range'=>'0.4 - 4.0']
]
],

[
'name'=>'B-HcG',
'price'=>6000,
'parameters'=>[
['name'=>'β-HCG','unit'=>'IU/L','reference_range'=>'< 5']
]
],

[
'name'=>'TFT',
'price'=>15000,
'parameters'=>[
['name'=>'TSH','unit'=>'mIU/L','reference_range'=>'0.4 - 4.0'],
['name'=>'T3','unit'=>'ng/dL','reference_range'=>'80 - 200'],
['name'=>'T4','unit'=>'µg/dL','reference_range'=>'5 - 12']
]
],

[
'name'=>'LH',
'price'=>7000,
'parameters'=>[
['name'=>'LH','unit'=>'IU/L','reference_range'=>'1.8 - 8.6']
]
],

[
'name'=>'FSH',
'price'=>7000,
'parameters'=>[
['name'=>'FSH','unit'=>'IU/L','reference_range'=>'1.5 - 12.4']
]
],

[
'name'=>'Prolactin',
'price'=>7000,
'parameters'=>[
['name'=>'Prolactin','unit'=>'ng/mL','reference_range'=>'4 - 15']
]
],

[
'name'=>'CRP',
'price'=>8000,
'parameters'=>[
['name'=>'CRP','unit'=>'mg/L','reference_range'=>'< 10']
]
]

];

foreach($chemicalTests as $test){

    $investigation = Investigation::create([
        'investigation_type_id'=>$chemical->id,
        'name'=>$test['name'],
        'price'=>$test['price']
    ]);

    foreach($test['parameters'] as $param){

        
    Parameter::create([
            'investigation_id'=>$investigation->id,
            'name'=>$param['name'],
            'unit'=>$param['unit'],
            'reference_range'=>$param['reference_range']
        ]);

    }

}



        /*
        |-----------------------------------------------------
        | HAEMATOLOGY
        |-----------------------------------------------------
        */

        $haemTests = [

[
'name'=>'Full Blood Count',
'price'=>3000,
'parameters'=>[
['name'=>'WBC','unit'=>'10^9/L','reference_range'=>'4 - 11'],
['name'=>'RBC','unit'=>'10^12/L','reference_range'=>'4.5 - 5.9'],
['name'=>'Hemoglobin','unit'=>'g/dL','reference_range'=>'13 - 17'],
['name'=>'Hematocrit','unit'=>'%','reference_range'=>'40 - 50'],
['name'=>'MCV','unit'=>'fL','reference_range'=>'80 - 96'],
['name'=>'MCH','unit'=>'pg','reference_range'=>'27 - 33'],
['name'=>'MCHC','unit'=>'g/dL','reference_range'=>'32 - 36'],
['name'=>'Platelets','unit'=>'10^9/L','reference_range'=>'150 - 400']
]
],

[
'name'=>'Packed Cell Volume',
'price'=>1000,
'parameters'=>[
['name'=>'PCV','unit'=>'%','reference_range'=>'40 - 50']
]
],

[
'name'=>'ESR',
'price'=>2000,
'parameters'=>[
['name'=>'ESR','unit'=>'mm/hr','reference_range'=>'0 - 15']
]
],

[
'name'=>'Genotype',
'price'=>1500,
'parameters'=>[
['name'=>'Genotype','unit'=>null,'reference_range'=>'AA']
]
],

[
'name'=>'Blood Group',
'price'=>1000,
'parameters'=>[
['name'=>'ABO Group','unit'=>null,'reference_range'=>'A, B, AB, O'],
['name'=>'Rhesus Factor','unit'=>null,'reference_range'=>'Positive/Negative']
]
],

[
'name'=>'Blood Grouping & Crossmatching',
'price'=>7500,
'parameters'=>[
['name'=>'ABO Group','unit'=>null,'reference_range'=>null],
['name'=>'Rhesus Factor','unit'=>null,'reference_range'=>null],
['name'=>'Crossmatch Result','unit'=>null,'reference_range'=>'Compatible']
]
],

[
'name'=>'RVS',
'price'=>2000,
'parameters'=>[
['name'=>'HIV Screening','unit'=>null,'reference_range'=>'Negative']
]
],

[
'name'=>'HBsAg',
'price'=>1700,
'parameters'=>[
['name'=>'HBsAg','unit'=>null,'reference_range'=>'Negative']
]
],

[
'name'=>'HCV',
'price'=>1700,
'parameters'=>[
['name'=>'HCV Antibody','unit'=>null,'reference_range'=>'Negative']
]
],

[
'name'=>'Clotting Time',
'price'=>2000,
'parameters'=>[
['name'=>'Clotting Time','unit'=>'minutes','reference_range'=>'5 - 11']
]
],

[
'name'=>'Hepatitis B Profile',
'price'=>6000,
'parameters'=>[
['name'=>'HBsAg','unit'=>null,'reference_range'=>'Negative'],
['name'=>'HBeAg','unit'=>null,'reference_range'=>'Negative'],
['name'=>'HBcAb','unit'=>null,'reference_range'=>'Negative']
]
],

[
'name'=>'DCT',
'price'=>3000,
'parameters'=>[
['name'=>'Direct Coombs Test','unit'=>null,'reference_range'=>'Negative']
]
],

[
'name'=>'ICT',
'price'=>3000,
'parameters'=>[
['name'=>'Indirect Coombs Test','unit'=>null,'reference_range'=>'Negative']
]
],

[
'name'=>'PT',
'price'=>3000,
'parameters'=>[
['name'=>'Prothrombin Time','unit'=>'seconds','reference_range'=>'11 - 13.5']
]
],

[
'name'=>'PTTK',
'price'=>3000,
'parameters'=>[
['name'=>'APTT','unit'=>'seconds','reference_range'=>'25 - 35']
]
],

[
'name'=>'Clotting Profile',
'price'=>6000,
'parameters'=>[
['name'=>'Prothrombin Time','unit'=>'seconds','reference_range'=>'11 - 13.5'],
['name'=>'APTT','unit'=>'seconds','reference_range'=>'25 - 35'],
['name'=>'INR','unit'=>null,'reference_range'=>'0.8 - 1.1']
]
]

];

foreach($haemTests as $test){

    $investigation = Investigation::create([
        'investigation_type_id'=>$haematology->id,
        'name'=>$test['name'],
        'price'=>$test['price']
    ]);

    foreach($test['parameters'] as $param){

        
    Parameter::create([
            'investigation_id'=>$investigation->id,
            'name'=>$param['name'],
            'unit'=>$param['unit'],
            'reference_range'=>$param['reference_range']
        ]);

    }

}



        /*
        |-----------------------------------------------------
        | MICROBIOLOGY
        |-----------------------------------------------------
        */

        $microTests = [

        [
            'name'=>'MP Microscopy',
            'price'=>1500,
            'parameters'=>[
            ['name'=>'Malaria Parasite','unit'=>null,'reference_range'=>'Negative'],
            ['name'=>'Parasite Density','unit'=>'parasites/µL','reference_range'=>null]
        ]
        ],

        [
        'name'=>'MP RDT',
        'price'=>800,
        'parameters'=>[
        ['name'=>'Malaria Antigen','unit'=>null,'reference_range'=>'Negative']
        ]
        ],

        [
        'name'=>'Widal',
        'price'=>1500,
        'parameters'=>[
        ['name'=>'Salmonella Typhi O','unit'=>'titre','reference_range'=>'<1:80'],
        ['name'=>'Salmonella Typhi H','unit'=>'titre','reference_range'=>'<1:80'],
        ['name'=>'Salmonella Paratyphi A','unit'=>'titre','reference_range'=>'<1:80'],
        ['name'=>'Salmonella Paratyphi B','unit'=>'titre','reference_range'=>'<1:80']
        ]
        ],

        [
        'name'=>'H. Pylori',
        'price'=>2000,
        'parameters'=>[
        ['name'=>'H. Pylori Antigen','unit'=>null,'reference_range'=>'Negative']
        ]
        ],

        [
        'name'=>'Stool Microscopy',
        'price'=>2000,
        'parameters'=>[
        ['name'=>'Color','unit'=>null,'reference_range'=>null],
        ['name'=>'Consistency','unit'=>null,'reference_range'=>null],
        ['name'=>'Ova','unit'=>null,'reference_range'=>'None Seen'],
        ['name'=>'Cysts','unit'=>null,'reference_range'=>'None Seen'],
        ['name'=>'Parasites','unit'=>null,'reference_range'=>'None Seen']
        ]
        ],

        [
        'name'=>'Urine Microscopy',
        'price'=>2000,
        'parameters'=>[
        ['name'=>'Pus Cells','unit'=>'/HPF','reference_range'=>'0-5'],
        ['name'=>'Red Blood Cells','unit'=>'/HPF','reference_range'=>'0-3'],
        ['name'=>'Epithelial Cells','unit'=>'/HPF','reference_range'=>'Few'],
        ['name'=>'Bacteria','unit'=>null,'reference_range'=>'None']
        ]
        ],

        [
        'name'=>'Stool MCS',
        'price'=>4000,
        'parameters'=>[
        ['name'=>'Microscopy','unit'=>null,'reference_range'=>null],
        ['name'=>'Culture','unit'=>null,'reference_range'=>'No Growth'],
        ['name'=>'Sensitivity','unit'=>null,'reference_range'=>null]
        ]
        ],

        [
        'name'=>'Urine MCS',
        'price'=>4000,
        'parameters'=>[
        ['name'=>'Microscopy','unit'=>null,'reference_range'=>null],
        ['name'=>'Culture','unit'=>null,'reference_range'=>'No Growth'],
        ['name'=>'Sensitivity','unit'=>null,'reference_range'=>null]
        ]
        ],

        [
        'name'=>'Sputum MCS',
        'price'=>4000,
        'parameters'=>[
        ['name'=>'Microscopy','unit'=>null,'reference_range'=>null],
        ['name'=>'Culture','unit'=>null,'reference_range'=>'No Growth'],
        ['name'=>'Sensitivity','unit'=>null,'reference_range'=>null]
        ]
        ],

        [
        'name'=>'Mantoux',
        'price'=>3000,
        'parameters'=>[
        ['name'=>'Induration Size','unit'=>'mm','reference_range'=>'<10']
        ]
        ],

        [
        'name'=>'Swab MCS',
        'price'=>4000,
        'parameters'=>[
        ['name'=>'Microscopy','unit'=>null,'reference_range'=>null],
        ['name'=>'Culture','unit'=>null,'reference_range'=>'No Growth'],
        ['name'=>'Sensitivity','unit'=>null,'reference_range'=>null]
        ]
        ],

        [
        'name'=>'HVS MCS',
        'price'=>4000,
        'parameters'=>[
        ['name'=>'Microscopy','unit'=>null,'reference_range'=>null],
        ['name'=>'Culture','unit'=>null,'reference_range'=>'No Growth'],
        ['name'=>'Sensitivity','unit'=>null,'reference_range'=>null]
        ]
        ],

        [
        'name'=>'ECS MCS',
        'price'=>4000,
        'parameters'=>[
        ['name'=>'Microscopy','unit'=>null,'reference_range'=>null],
        ['name'=>'Culture','unit'=>null,'reference_range'=>'No Growth'],
        ['name'=>'Sensitivity','unit'=>null,'reference_range'=>null]
        ]
        ],

        [
        'name'=>'VDRL',
        'price'=>1500,
        'parameters'=>[
        ['name'=>'VDRL','unit'=>null,'reference_range'=>'Non-Reactive']
        ]
        ]

        ];
foreach($microTests as $test){

    $investigation = Investigation::create([
        'investigation_type_id'=>$microbiology->id,
        'name'=>$test['name'],
        'price'=>$test['price']
    ]);

    foreach($test['parameters'] as $param){

        
    Parameter::create([
            'investigation_id'=>$investigation->id,
            'name'=>$param['name'],
            'unit'=>$param['unit'],
            'reference_range'=>$param['reference_range']
        ]);

    }

}

        $otherDepartments = [
            'Accident & Emergency (A&E) / Emergency Department',
            'General Outpatient Department (GOPD)',
            'Nursing Services',
            'Medical Services',
            'Pediatrics',
            'Family Medicine',
            'Obstetrics & Gynecology',
        ];

        foreach($otherDepartments as $department){
            Department::firstOrCreate(['name'=>$department]);
        }

    }
}