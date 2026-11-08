<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\PatientVisit;
use App\Models\InvestigationRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DailyReportExport;

class ReportsController extends Controller
{
    public function index()
{
        return view('reports.index');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'from_date' => 'nullable|date|before_or_equal:to_date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $user = Auth::user();

        if($request->from_date && $request->to_date) {
            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate = Carbon::parse($request->to_date)->endOfDay();
        }

        return view('reports.show')->with([
            'date' => $request->date,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date
        ]);
        
    }

}