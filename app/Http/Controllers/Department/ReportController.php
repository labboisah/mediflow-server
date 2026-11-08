<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\Expense;
use App\Models\Consumable;
use App\Models\ConsumableStock;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\PDF;

class ReportController extends Controller
{
    public function index() {
        return view('department.reports.index');
    }

    public function generate(Request $request)
    {
        $start = $request->start_date;
        
        $end = $request->end_date;
        
        $department = auth()->user()->department;

        $expenses = $department->expensesFrom($request->start_date, $request->end_date);

        $revenue = $department->revenuesFrom($request->start_date, $request->end_date);

        $consumables = $department->consumablesFrom($request->start_date, $request->end_date);

        $profits = $revenue - ($consumables + $expenses);

        return view('department.reports.view',compact('start','end','expenses','revenue','consumables', 'profits'));

    }

    public function pdf(Request $request) {
        $data = [
            'revenue'=>$request->revenue,
            'profits'=>$request->profits,
            'expenses'=>$request->expenses,
            'consumables'=>$request->consumables,
        ];

        $pdf = PDF::loadView('department.reports.view',$data);
        return $pdf->download('department-report.pdf');
    }

    
}
