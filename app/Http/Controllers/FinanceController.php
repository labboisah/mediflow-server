<?php

namespace App\Http\Controllers;

use App\Models\SalaryPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
    public function index()
    {
        $selectedMonth = request('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        /* =========================
        TODAY CALCULATIONS
        ========================= */

        $todayBills = \App\Models\Bill::whereDate('created_at', today())->get();

        $todayGross = $todayBills->sum('total_amount');
        $todayDiscount = $todayBills->sum('discount_amount');

        $todayNetRevenue = $todayGross - $todayDiscount;

        $todayStaffShare = $todayNetRevenue * 0.4;
        $todayAnnexShare = $todayNetRevenue * 0.6;

        $todayExpenses = \App\Models\Expense::whereDate('expense_date', today())
                            ->sum('amount');

        $todayProfit = $todayAnnexShare - $todayExpenses;


        /* =========================
        MONTH CALCULATIONS
        ========================= */

        $monthBills = \App\Models\Bill::whereBetween('created_at', [$monthStart, $monthEnd])->get();

        $monthGross = $monthBills->sum('total_amount');
        $monthDiscount = $monthBills->sum('discount_amount');

        $monthNetRevenue = $monthGross - $monthDiscount;

        $monthStaffShare = $monthNetRevenue * 0.4;
        $monthAnnexShare = $monthNetRevenue * 0.6;
        

        $monthExpenses = \App\Models\Expense::whereBetween('expense_date', [
                                $monthStart->toDateString(),
                                $monthEnd->toDateString(),
                            ])
                            ->sum('amount');

        $salaryPayment = SalaryPayment::whereDate('salary_month', $monthStart->toDateString())->first();
        $monthSalaryPaid = $salaryPayment?->amount ?? 0;
        $monthTotalOutflow = $monthExpenses + $monthSalaryPaid;
        $monthProfit = $monthAnnexShare - $monthTotalOutflow;
        $salaryPayments = SalaryPayment::with('user')->latest('salary_month')->paginate(12);

        $report = auth()->user()->finance();
        return view('admin.finances.index', compact(
            'todayGross',
            'todayDiscount',
            'todayNetRevenue',
            'todayStaffShare',
            'todayAnnexShare',
            'todayExpenses',
            'todayProfit',
            'monthGross',
            'monthDiscount',
            'monthNetRevenue',
            'monthStaffShare',
            'monthAnnexShare',
            'monthExpenses',
            'monthSalaryPaid',
            'monthTotalOutflow',
            'monthProfit',
            'report',
            'selectedMonth',
            'salaryPayment',
            'salaryPayments'
        ));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'salary_month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $salaryMonth = Carbon::createFromFormat('Y-m', $validated['salary_month'])
            ->startOfMonth()
            ->toDateString();

        SalaryPayment::updateOrCreate(
            ['salary_month' => $salaryMonth],
            [
                'amount' => $validated['amount'],
                'note' => $validated['note'] ?? null,
                'user_id' => auth()->id(),
            ]
        );

        return redirect()
            ->route('admin.finances.index', ['month' => $validated['salary_month']])
            ->with('success', 'Salary payment recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
