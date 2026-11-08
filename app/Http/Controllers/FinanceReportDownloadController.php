<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Revenue;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FinanceReportDownloadController extends Controller
{
    public function expenses(Request $request)
    {
        $expenses = $this->filteredExpenses($request)
            ->latest('expense_date')
            ->get();

        $summary = [
            'count' => $expenses->count(),
            'total' => $expenses->sum('amount'),
            'average' => $expenses->avg('amount') ?? 0,
        ];

        $pdf = Pdf::loadView('reports.pdf.expense-record-report', [
            'records' => $expenses,
            'summary' => $summary,
            'filters' => $this->filters($request),
            'hospital' => $this->hospitalHeaderData(),
            'generatedBy' => $request->user(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('expense-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function revenues(Request $request)
    {
        $revenues = $this->filteredRevenues($request)
            ->latest('revenue_date')
            ->get();

        $summary = [
            'count' => $revenues->count(),
            'total' => $revenues->sum('amount'),
            'average' => $revenues->avg('amount') ?? 0,
        ];

        $pdf = Pdf::loadView('reports.pdf.revenue-record-report', [
            'records' => $revenues,
            'summary' => $summary,
            'filters' => $this->filters($request),
            'hospital' => $this->hospitalHeaderData(),
            'generatedBy' => $request->user(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('revenue-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function filteredExpenses(Request $request): Builder
    {
        return Expense::query()
            ->with(['category', 'department', 'createdBy'])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->input('search');

                $query->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn (Builder $department) => $department->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('createdBy', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('expense_category_id', $request->input('category')))
            ->when($request->filled('department'), fn (Builder $query) => $query->where('department_id', $request->input('department')))
            ->when($request->filled('created_by'), fn (Builder $query) => $query->where('created_by', $request->input('created_by')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('expense_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('expense_date', '<=', $request->input('date_to')));
    }

    private function filteredRevenues(Request $request): Builder
    {
        return Revenue::query()
            ->with(['category', 'department', 'createdBy'])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->input('search');

                $query->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn (Builder $department) => $department->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('createdBy', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('revenue_category_id', $request->input('category')))
            ->when($request->filled('department'), function (Builder $query) use ($request) {
                $request->input('department') === 'general'
                    ? $query->whereNull('department_id')
                    : $query->where('department_id', $request->input('department'));
            })
            ->when($request->filled('created_by'), fn (Builder $query) => $query->where('created_by', $request->input('created_by')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('revenue_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('revenue_date', '<=', $request->input('date_to')));
    }

    private function filters(Request $request): array
    {
        return [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
        ];
    }

    private function hospitalHeaderData(): array
    {
        return [
            'name' => strtoupper(config('app.title', config('app.name', 'FAYHOS'))),
            'address' => strtoupper(config('app.address', '')),
            'logo' => public_path('images/logo.png'),
        ];
    }
}
