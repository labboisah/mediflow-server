<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Labour;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    

    public function index()
    {
        $search = trim((string) request('q'));

        $deliveries = Delivery::query()
            ->with(['patient.demographic', 'deliveredBy', 'labour'])
            ->when($search !== '', fn ($query) => $this->searchMaternityPatient($query, $search))
            ->latest('delivery_date_time')
            ->get();

        return view('midwife.delivery.index', compact('deliveries', 'search'));
    }

    private function searchMaternityPatient($query, string $search)
    {
        $like = "%{$search}%";

        return $query->where(function ($query) use ($like) {
            $query
                ->where('delivery_status', 'like', $like)
                ->orWhere('delivery_type', 'like', $like)
                ->orWhereHas('patient', function ($patientQuery) use ($like) {
                    $patientQuery
                        ->where('hospital_number', 'like', $like)
                        ->orWhereHas('demographic', function ($demographicQuery) use ($like) {
                            $demographicQuery
                                ->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('phone_number', 'like', $like);
                        });
                });
        });
    }

    public function create(Labour $labour)
    {
        $age = $labour->patient->age();

        if ($labour->patient->demographic->gender !== 'Female' || $age < 13 || $age > 55) {
            return redirect()->route('midwife.delivery.index')
                ->with('error', 'Deliveries can only be created for female patients aged 13-55 years.');
        }

        return view('midwife.delivery.create', compact('labour'));
    }

    
    public function store(Request $request, Labour $labour)
    {
    $validated = $request->validate([

        /*
        |--------------------------------------------------------------------------
        | Delivery Details
        |--------------------------------------------------------------------------
        */

        'delivery_date_time' => [
            'nullable',
            'date',
        ],

        'delivery_type' => [
            'nullable',
            'in:vaginal,assisted_vaginal,caesarean',
        ],

        'reason_for_delivery_type' => [
            'nullable',
            'string',
            'max:3000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Assisted Vaginal Delivery
        |--------------------------------------------------------------------------
        */

        'assisted_with' => [
            'nullable',
            'in:vacuum,forceps',
        ],

        'indication_for_assistance' => [
            'nullable',
            'string',
            'max:3000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Caesarean Section
        |--------------------------------------------------------------------------
        */

        'caesarean_type' => [
            'nullable',
            'in:elective,emergency',
        ],

        'indication_for_caesarean' => [
            'nullable',
            'string',
            'max:3000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Perineal Trauma
        |--------------------------------------------------------------------------
        */

        'perineal_trauma' => [
            'nullable',
            'in:intact,1st degree,2nd degree,3rd degree,4th degree',
        ],

        'episiotomy' => [
            'nullable',
            'string',
            'max:3000',
        ],

        'perineal_repair' => [
            'nullable',
            'string',
            'max:3000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Third Stage
        |--------------------------------------------------------------------------
        */

        'placenta_delivery_method' => [
            'nullable',
            'in:spontaneous,manual removal',
        ],

        'placenta_delivered_at' => [
            'nullable',
            'date',
        ],

        'placental_examination' => [
            'nullable',
            'string',
            'max:3000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Maternal Condition
        |--------------------------------------------------------------------------
        */

        'estimated_blood_loss' => [
            'nullable',
            'string',
            'max:255',
        ],

        'blood_loss_assessment' => [
            'nullable',
            'string',
            'max:3000',
        ],

        'uterine_tone' => [
            'nullable',
            'string',
            'max:255',
        ],

        'per_vaginal_bleeding' => [
            'nullable',
            'string',
            'max:255',
        ],

        'blood_pressure' => [
            'nullable',
            'string',
            'max:50',
        ],

        'pulse_rate' => [
            'nullable',
            'string',
            'max:50',
        ],

        'general_condition' => [
            'nullable',
            'string',
            'max:255',
        ],

        /*
        |--------------------------------------------------------------------------
        | Complications
        |--------------------------------------------------------------------------
        */

        'complications' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'management_of_complications' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Delivery Outcome
        |--------------------------------------------------------------------------
        */

        'number_of_babies' => [
            'nullable',
            'integer',
            'min:1',
            'max:10',
        ],

        'delivery_summary' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'delivery_status' => [
            'nullable',
            'in:successful,complicated,maternal_death,fetal_death',
        ],

    ]);

    $validated['delivery_date_time'] = $validated['delivery_date_time'] ?? now();
    $validated['delivery_type'] = $validated['delivery_type'] ?? 'vaginal';
    $validated['number_of_babies'] = $validated['number_of_babies'] ?? 1;
    $validated['delivery_status'] = $validated['delivery_status'] ?? 'successful';
    $validated['placenta_delivered_at'] = $validated['placenta_delivered_at'] ?? $validated['delivery_date_time'];

    /*
    |--------------------------------------------------------------------------
    | Create Delivery Record
    |--------------------------------------------------------------------------
    */

    $delivery = Delivery::create([

        'labour_id' => $labour->id,

        'patient_id' => $labour->patient_id,

        'patient_visit_id' => $labour->patient_visit_id ?? $labour->patient?->currentVisit()?->id,

        'delivered_by' => auth()->id(),

        'assisted_by' => $request->assisted_by ?? auth()->id(),

        /*
        |--------------------------------------------------------------------------
        | Delivery Details
        |--------------------------------------------------------------------------
        */

        'delivery_date_time' => $validated['delivery_date_time'],

        'delivery_type' => $validated['delivery_type'],

        'reason_for_delivery_type' => $validated['reason_for_delivery_type'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Assisted Vaginal Delivery
        |--------------------------------------------------------------------------
        */

        'assisted_with' => $validated['assisted_with'] ?? null,

        'indication_for_assistance' => $validated['indication_for_assistance'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Caesarean Section
        |--------------------------------------------------------------------------
        */

        'caesarean_type' => $validated['caesarean_type'] ?? null,

        'indication_for_caesarean' => $validated['indication_for_caesarean'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Perineal Trauma
        |--------------------------------------------------------------------------
        */

        'perineal_trauma' => $validated['perineal_trauma'] ?? null,

        'episiotomy' => $validated['episiotomy'] ?? null,

        'perineal_repair' => $validated['perineal_repair'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Third Stage
        |--------------------------------------------------------------------------
        */

        'placenta_delivery_method' => $validated['placenta_delivery_method'] ?? null,

        'placenta_delivered_at' => $validated['placenta_delivered_at'] ?? null,

        'placental_examination' => $validated['placental_examination'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Maternal Condition
        |--------------------------------------------------------------------------
        */

        'estimated_blood_loss' => $validated['estimated_blood_loss'] ?? null,

        'blood_loss_assessment' => $validated['blood_loss_assessment'] ?? null,

        'uterine_tone' => $validated['uterine_tone'] ?? null,

        'per_vaginal_bleeding' => $validated['per_vaginal_bleeding'] ?? null,

        'blood_pressure' => $validated['blood_pressure'] ?? null,

        'pulse_rate' => $validated['pulse_rate'] ?? null,

        'general_condition' => $validated['general_condition'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Complications
        |--------------------------------------------------------------------------
        */

        'complications' => $validated['complications'] ?? null,

        'management_of_complications' => $validated['management_of_complications'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Delivery Outcome
        |--------------------------------------------------------------------------
        */

        'number_of_babies' => $validated['number_of_babies'] ?? 1,

        'delivery_summary' => $validated['delivery_summary'] ?? null,

        'delivery_status' => $validated['delivery_status'] ?? 'successful',

    ]);

    /*
    |--------------------------------------------------------------------------
    | Update Labour Status
    |--------------------------------------------------------------------------
    */

    $labour->update([

        'status' => 'completed',

        'stage' => 'completed',

    ]);
    // Log activity
    $labour->patient->currentVisit()->visitActivities()->create([
        'activity' => "Delivery registered with status: {$delivery->delivery_status}",
        'recorded_by' => auth()->id(),
    ]);
    
    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route('midwife.delivery.show', $delivery)
        ->with('success', 'Delivery registered successfully.');
}
    

    public function show(Delivery $delivery)
    {
        $delivery->load('patient.demographic', 'labour', 'deliveredBy', 'assistedBy', 'newborns');

        return view('midwife.delivery.show', compact('delivery'));
    }

    public function edit(Delivery $delivery)
    {
        $delivery->load('patient.demographic', 'labour');

        return view('midwife.delivery.edit', compact('delivery'));
    }

    public function update(Request $request, Delivery $delivery)
{
    $validated = $request->validate([

        /*
        |--------------------------------------------------------------------------
        | Delivery Details
        |--------------------------------------------------------------------------
        */

        'delivery_date_time' => [
                'nullable',
                'date',
            ],

            'delivery_type' => [
                'nullable',                
                'string',
                'max:255',
            ],

        /*
        --------------------------------------------------------------------------        | Assisted Vaginal Delivery
        |--------------------------------------------------------------------------
        */

        'assisted_with' => [
            'nullable',
            'in:vacuum,forceps',
        ],

        'indication_for_assistance' => [
            'nullable',
            'string',
            'max:3000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Caesarean Section
        |--------------------------------------------------------------------------
        */

        'caesarean_type' => [
            'nullable',
            'in:elective,emergency',
        ],

        'indication_for_caesarean' => [
            'nullable',
            'string',
            'max:3000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Perineal Trauma
        |--------------------------------------------------------------------------
        */

        'perineal_trauma' => [
            'nullable',
            'in:intact,1st degree,2nd degree,3rd degree,4th degree',
        ],

        'episiotomy' => [
            'nullable',
            'string',
            'max:3000',
        ],

        'perineal_repair' => [
            'nullable',
            'string',
            'max:3000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Third Stage
        |--------------------------------------------------------------------------
        */

        'placenta_delivery_method' => [
            'nullable',
            'in:spontaneous,manual removal',
        ],

        'placenta_delivered_at' => [
            'nullable',
            'date',
        ],

        'placental_examination' => [
            'nullable',
            'string',
            'max:3000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Maternal Condition
        |--------------------------------------------------------------------------
        */

        'estimated_blood_loss' => [
            'nullable',
            'string',
            'max:255',
        ],

        'blood_loss_assessment' => [
            'nullable',
            'string',
            'max:3000',
        ],

        'uterine_tone' => [
            'nullable',
            'string',
            'max:255',
        ],

        'per_vaginal_bleeding' => [
            'nullable',
            'string',
            'max:255',
        ],

        'blood_pressure' => [
            'nullable',
            'string',
            'max:50',
        ],

        'pulse_rate' => [
            'nullable',
            'string',
            'max:50',
        ],

        'general_condition' => [
            'nullable',
            'string',
            'max:255',
        ],

        /*
        |--------------------------------------------------------------------------
        | Complications
        |--------------------------------------------------------------------------
        */

        'complications' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'management_of_complications' => [
            'nullable',
            'string',
            'max:5000',
        ],

        /*
        |--------------------------------------------------------------------------
        | Delivery Outcome
        |--------------------------------------------------------------------------
        */

        'number_of_babies' => [
            'nullable',
            'integer',
            'min:1',
            'max:10',
        ],

        'delivery_summary' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'delivery_status' => [
            'nullable',
            'in:successful,complicated,maternal_death,fetal_death',
        ],

    ]);
    $validated['delivery_date_time'] = $validated['delivery_date_time'] ?? $delivery->delivery_date_time ?? now();
    $validated['delivery_type'] = $validated['delivery_type'] ?? $delivery->delivery_type ?? 'vaginal';
    $validated['placenta_delivered_at'] = $validated['placenta_delivered_at'] ?? $delivery->placenta_delivered_at ?? $validated['delivery_date_time'];
    $validated['number_of_babies'] = $validated['number_of_babies'] ?? $delivery->number_of_babies ?? 1;
    $validated['delivery_status'] = $validated['delivery_status'] ?? $delivery->delivery_status ?? 'successful';

    /*
    |--------------------------------------------------------------------------
    | Update Delivery Record
    |--------------------------------------------------------------------------
    */

    $delivery->update([

        /*
        |--------------------------------------------------------------------------
        | Delivery Details
        |--------------------------------------------------------------------------
        */

        'delivery_date_time' => $validated['delivery_date_time'],

        'delivery_type' => $validated['delivery_type'],

        'reason_for_delivery_type' => $validated['reason_for_delivery_type'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Assisted Vaginal Delivery
        |--------------------------------------------------------------------------
        */

        'assisted_with' => $validated['assisted_with'] ?? null,

        'indication_for_assistance' => $validated['indication_for_assistance'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Caesarean Section
        |--------------------------------------------------------------------------
        */

        'caesarean_type' => $validated['caesarean_type'] ?? null,

        'indication_for_caesarean' => $validated['indication_for_caesarean'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Perineal Trauma
        |--------------------------------------------------------------------------
        */

        'perineal_trauma' => $validated['perineal_trauma'] ?? null,

        'episiotomy' => $validated['episiotomy'] ?? null,

        'perineal_repair' => $validated['perineal_repair'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Third Stage
        |--------------------------------------------------------------------------
        */

        'placenta_delivery_method' => $validated['placenta_delivery_method'] ?? null,

        'placenta_delivered_at' => $validated['placenta_delivered_at'] ?? null,

        'placental_examination' => $validated['placental_examination'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Maternal Condition
        |--------------------------------------------------------------------------
        */

        'estimated_blood_loss' => $validated['estimated_blood_loss'] ?? null,

        'blood_loss_assessment' => $validated['blood_loss_assessment'] ?? null,

        'uterine_tone' => $validated['uterine_tone'] ?? null,

        'per_vaginal_bleeding' => $validated['per_vaginal_bleeding'] ?? null,

        'blood_pressure' => $validated['blood_pressure'] ?? null,

        'pulse_rate' => $validated['pulse_rate'] ?? null,

        'general_condition' => $validated['general_condition'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Complications
        |--------------------------------------------------------------------------
        */

        'complications' => $validated['complications'] ?? null,

        'management_of_complications' => $validated['management_of_complications'] ?? null,

        /*
        |--------------------------------------------------------------------------
        | Delivery Outcome
        |--------------------------------------------------------------------------
        */

        'number_of_babies' => $validated['number_of_babies'],

        'delivery_summary' => $validated['delivery_summary'] ?? null,

        'delivery_status' => $validated['delivery_status'],

    ]);

    /*
    |--------------------------------------------------------------------------
    | Update Related Labour Status
    |--------------------------------------------------------------------------
    */

    if ($delivery->delivery_status == 'successful' && $delivery->labour) {

        $delivery->labour->update([

            'status' => 'completed',

            'stage' => 'completed',

        ]);

    } elseif ($delivery->delivery_status == 'complicated' && $delivery->labour) {

        $delivery->labour->update([

            'status' => 'complicated',

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route('midwife.delivery.show', $delivery)
        ->with('success', 'Delivery record updated successfully.');
}
    public function destroy(Delivery $delivery)
    {
        $patient = $delivery->patient;

        $delivery->delete();

        

        return redirect()->route('patient.show', $patient)
            ->with('success', 'Delivery record deleted successfully.');
    }

    public function patientRecords(Patient $patient)
    {
        $patient->load('demographic', 'deliveries.labour');

        $deliveries = $patient->deliveries()->orderByDesc('delivery_date_time')->get();

        return view('midwife.delivery.patient-records', compact('patient', 'deliveries'));
    }
}
