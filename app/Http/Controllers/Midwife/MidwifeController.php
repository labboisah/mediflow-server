<?php

namespace App\Http\Controllers\Midwife;

use App\Http\Controllers\Controller;
use App\Models\AntenatalCare;
use App\Models\Labour;
use App\Models\Delivery;
use App\Models\Newborn;
use App\Models\NewbornExamination;
use App\Models\PostnatalExamination;
use App\Models\ChildFollowUp;
use App\Models\Patient;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MidwifeController extends Controller
{
    /**
     * Display the midwife dashboard
     */
    public function dashboard()
    {
        $data = [
            // Antenatal Care Statistics
            'antenatal_total' => AntenatalCare::count(),
            'antenatal_today' => AntenatalCare::whereDate('created_at', today())->count(),
            'antenatal_this_month' => AntenatalCare::whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])->count(),
            'pregnant_patients' => Patient::whereHas('antenatalCares')->count(),
            
            // Labour Statistics
            'labour_total' => Labour::count(),
            'labour_today' => Labour::whereDate('created_at', today())->count(),
            'labour_this_month' => Labour::whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])->count(),
            'labour_in_progress' => Labour::where('status', 'in_progress')->count(),
            'labour_completed' => Labour::where('status', 'completed')->count(),
            
            // Delivery Statistics
            'delivery_total' => Delivery::count(),
            'delivery_today' => Delivery::whereDate('created_at', today())->count(),
            'delivery_this_month' => Delivery::whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])->count(),
            'vaginal_deliveries' => Delivery::where('delivery_type', 'vaginal')->count(),
            'caesarean_deliveries' => Delivery::where('delivery_type', 'caesarean')->count(),
            
            // Newborn Statistics
            'newborn_total' => Newborn::count(),
            'newborn_today' => Newborn::whereDate('created_at', today())->count(),
            'newborn_males' => Newborn::where('sex', 'male')->count(),
            'newborn_females' => Newborn::where('sex', 'female')->count(),
            'newborn_healthy' => Newborn::where('status', 'healthy')->count(),
            'newborn_at_risk' => Newborn::where('status', 'at_risk')->count(),
            
            // Examination Statistics
            'newborn_examinations_total' => NewbornExamination::count(),
            'postnatal_examinations_total' => PostnatalExamination::count(),
            'postnatal_normal' => PostnatalExamination::where('recovery_status', 'normal')->count(),
            'postnatal_at_risk' => PostnatalExamination::where('recovery_status', 'at_risk')->count(),
            'child_follow_ups_total' => ChildFollowUp::count(),
            'child_follow_ups_today' => ChildFollowUp::whereDate('created_at', today())->count(),
            
            // Recent Records
            'recent_antenatal' => AntenatalCare::with('patient')
                ->latest()
                ->limit(5)
                ->get(),
            'recent_deliveries' => Delivery::with('patient')
                ->latest()
                ->limit(5)
                ->get(),
            'recent_newborns' => Newborn::with('delivery.patient')
                ->latest()
                ->limit(5)
                ->get(),
            'recent_follow_ups' => ChildFollowUp::with('newborn')
                ->latest()
                ->limit(5)
                ->get(),
        ];

        return view('midwife.dashboard', $data);
    }

    /**
     * Display the progress of a specific patient
      */
    public function progress(Patient $patient)
{
    /*
    |--------------------------------------------------------------------------
    | Antenatal Care
    |--------------------------------------------------------------------------
    */

    $antenatalCare = AntenatalCare::where('patient_id', $patient->id)
        ->latest()
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Labour
    |--------------------------------------------------------------------------
    */

    $labour = Labour::where('patient_id', $patient->id)
        ->latest()
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Delivery
    |--------------------------------------------------------------------------
    */

    $delivery = Delivery::where('patient_id', $patient->id)
        ->latest()
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Newborns
    |--------------------------------------------------------------------------
    */

    $newborns = Newborn::where('patient_id', $patient->id)
        ->latest()
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Newborn Examinations
    |--------------------------------------------------------------------------
    */

    $newbornExaminations = NewbornExamination::whereIn(
            'newborn_id',
            $newborns->pluck('id')
        )
        ->latest()
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Postnatal Examinations
    |--------------------------------------------------------------------------
    */

    $postnatalExaminations = collect();

    if ($delivery) {

        $postnatalExaminations = PostnatalExamination::where(
                'delivery_id',
                $delivery->id
            )
            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Child Follow-ups
    |--------------------------------------------------------------------------
    */

    $childFollowUps = ChildFollowUp::whereIn(
            'newborn_id',
            $newborns->pluck('id')
        )
        ->latest()
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Render View
    |--------------------------------------------------------------------------
    */

    return view('midwife.progress', [

        'patient' => $patient,

        'antenatalCare' => $antenatalCare,

        'labour' => $labour,

        'delivery' => $delivery,

        'newborns' => $newborns,

        'newbornExaminations' => $newbornExaminations,

        'postnatalExaminations' => $postnatalExaminations,

        'childFollowUps' => $childFollowUps,

    ]);
}
}
