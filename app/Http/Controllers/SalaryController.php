<?php

namespace App\Http\Controllers;

use App\Models\SalaryPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $request->query('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $salaryPayment = SalaryPayment::whereDate('salary_month', $monthStart->toDateString())->first();

        $salaryPayments = SalaryPayment::with('user')
            ->latest('salary_month')
            ->paginate(15);

        $totalPaid = SalaryPayment::sum('amount');
        $yearPaid = SalaryPayment::whereYear('salary_month', $monthStart->year)->sum('amount');
        $monthPaid = $salaryPayment?->amount ?? 0;

        return view('admin.salaries.index', compact(
            'selectedMonth',
            'salaryPayment',
            'salaryPayments',
            'monthPaid',
            'yearPaid',
            'totalPaid'
        ));
    }

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
            ->route('admin.salaries.index', ['month' => $validated['salary_month']])
            ->with('success', 'Salary payment recorded successfully.');
    }

    public function destroy(SalaryPayment $salary)
    {
        $salary->delete();

        return redirect()
            ->route('admin.salaries.index')
            ->with('success', 'Salary payment deleted successfully.');
    }
}
