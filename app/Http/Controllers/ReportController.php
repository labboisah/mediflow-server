<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Report;
use App\Models\SalaryPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use App\Exports\FinancialReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Check if the authenticated user is an admin
     */
    private function isAdmin()
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function index()
    {
        $reports = Report::with('user')->latest()->paginate(20);

        $stats = Report::selectRaw(
            'COUNT(*) as total_reports, '
            .'SUM(gross) as total_gross, '
            .'SUM(discount) as total_discount, '
            .'SUM(net) as total_net, '
            .'SUM(staff_share) as total_staff_share, '
            .'SUM(annex_share) as total_annex_share, '
            .'SUM(radiologist_share) as total_radiologist_share, '
            .'SUM(radiographer_share) as total_radiographer_share, '
            .'SUM(expenses) as total_expenses, '
            .'SUM(salary_amount) as total_salary, '
            .'SUM(profit) as total_profit, '
            .'AVG(profit) as avg_profit'
        )->first();

        $users = User::orderBy('name')->get();

        return view('admin.reports.index', compact('reports', 'stats', 'users'));
    }

    public function generate(Request $request)
    {
        if ($request->today) {
            $from = now()->startOfDay();
            $to = now()->endOfDay();
        } else {
            $from = $request->from;
            $to = $request->to;
        }

        $selectedUserId = $request->user_id;

        $reports = Report::with('user')->latest()->paginate(20);

        $stats = Report::selectRaw(
            'COUNT(*) as total_reports, '
            .'SUM(gross) as total_gross, '
            .'SUM(discount) as total_discount, '
            .'SUM(net) as total_net, '
            .'SUM(staff_share) as total_staff_share, '
            .'SUM(annex_share) as total_annex_share, '
            .'SUM(radiologist_share) as total_radiologist_share, '
            .'SUM(radiographer_share) as total_radiographer_share, '
            .'SUM(expenses) as total_expenses, '
            .'SUM(salary_amount) as total_salary, '
            .'SUM(profit) as total_profit, '
            .'AVG(profit) as avg_profit'
        )->first();

        $users = User::orderBy('name')->get();

        $billsQuery = Bill::with('items.revenueDistribution','user')->whereBetween('created_at', [$from,$to]);
        $expensesQuery = Expense::whereBetween('expense_date', [$from,$to]);

        if ($this->isAdmin() && $selectedUserId) {
            $billsQuery->where('user_id', $selectedUserId);
            $expensesQuery->where('user_id', $selectedUserId);
        } elseif (!$this->isAdmin()) {
            $billsQuery->where('user_id', auth()->id());
            $expensesQuery->where('user_id', auth()->id());
            $selectedUserId = auth()->id();
        }

        $bills = $billsQuery->get();
        $expenses = $expensesQuery->get();

        $gross = $bills->sum('total_amount');
        $discount = $bills->sum('discount_amount');
        $net = $gross - $discount;

        // Calculate shares based on actual distributions
        $staffShare = 0;
        $annexShare = 0;
        $radiologistShare = 0;
        $radiographerShare = 0;
        foreach ($bills as $bill) {
            foreach ($bill->items as $item) {
                $distribution = $item->revenueDistribution;
                if ($distribution) {
                    $staffShare += $distribution->staff_amount;
                    $annexShare += $distribution->annex_amount;
                    $radiologistShare += $distribution->radiologist_amount;
                    $radiographerShare += $distribution->radiographer_amount;
                }
            }
        }

        $totalExpense = $expenses->sum('amount');
        $salaryAmount = $this->salaryAmountForRange($from, $to, $this->isAdmin() && ! $selectedUserId);
        $totalOutflow = $totalExpense + $salaryAmount;
        $profit = $annexShare - $totalOutflow;

        // Per-user breakdown for the current query range
        $userBreakdown = null;
        if ($request->type == 'user-breakdown') {
            $userAggregated = [];

            foreach ($bills as $bill) {
                $uid = $bill->user_id;
                if (!isset($userAggregated[$uid])) {
                    $userAggregated[$uid] = [
                        'user_id' => $uid,
                        'user_name' => $bill->user->name ?? 'Unknown',
                        'gross' => 0,
                        'discount' => 0,
                        'net' => 0,
                        'staff_share' => 0,
                        'annex_share' => 0,
                        'radiologist_share' => 0,
                        'radiographer_share' => 0,
                        'expenses' => 0,
                        'salary_amount' => 0,
                        'profit' => 0,
                    ];
                }

                $userAggregated[$uid]['gross'] += $bill->total_amount;
                $userAggregated[$uid]['discount'] += $bill->discount_amount;
                $userAggregated[$uid]['net'] += ($bill->total_amount - $bill->discount_amount);

                foreach ($bill->items as $item) {
                    $distribution = $item->revenueDistribution;
                    if ($distribution) {
                        $userAggregated[$uid]['staff_share'] += $distribution->staff_amount;
                        $userAggregated[$uid]['annex_share'] += $distribution->annex_amount;
                        $userAggregated[$uid]['radiologist_share'] += $distribution->radiologist_amount;
                        $userAggregated[$uid]['radiographer_share'] += $distribution->radiographer_amount;
                    }
                }
            }

            foreach ($expenses as $expense) {
                $uid = $expense->user_id;
                if (!isset($userAggregated[$uid])) {
                    $userAggregated[$uid] = [
                        'user_id' => $uid,
                        'user_name' => optional($expense->user)->name ?? 'Unknown',
                        'gross' => 0,
                        'discount' => 0,
                        'net' => 0,
                        'staff_share' => 0,
                        'annex_share' => 0,
                        'radiologist_share' => 0,
                        'radiographer_share' => 0,
                        'expenses' => 0,
                        'salary_amount' => 0,
                        'profit' => 0,
                    ];
                }
                $userAggregated[$uid]['expenses'] += $expense->amount;
            }

            foreach ($userAggregated as &$entry) {
                $entry['profit'] = $entry['annex_share'] - ($entry['expenses'] + $entry['salary_amount']);
            }

            $userBreakdown = collect($userAggregated)->values();

            return view('admin.reports.index', compact('reports', 'stats', 'users', 'userBreakdown', 'from', 'to', 'selectedUserId'));
        }

        $reporterName = auth()->user()->name;
        $reporterEmail = auth()->user()->email;
        $generatedDate = now()->format('d M Y');
        $generatedTime = now()->format('h:i A');
        $reportId = 'ANNEX-' . now()->format('YmdHis');

        $data = compact(
            'bills','expenses',
            'gross','discount','net',
            'staffShare',
            'annexShare',
            'radiologistShare',
            'radiographerShare',
            'totalExpense',
            'salaryAmount',
            'totalOutflow',
            'profit',
            'from','to',
            'reporterName',
            'reporterEmail',
            'generatedDate',
            'generatedTime',
            'reportId'
        );

        if ($request->type == 'summary') {
            return view('admin.reports.summary', $data);
        }

        return view('view.reports.detailed', $data);
    }

    public function exportCsv(Request $request)
    {
        $filename = "financial-report.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function() use ($request) {

            $file = fopen('php://output','w');

            fputcsv($file, ['Gross','Discount','Net','Staff Share','Annex Share','Radiologist Share','Radiographer Share','Expenses','Salary','Total Outflow','Profit']);

            // Calculate data
            $from = $request->from;
            $to = $request->to;
            $billsQuery = Bill::with('items.revenueDistribution')->whereBetween('created_at', [$from,$to]);
            $expensesQuery = Expense::whereBetween('expense_date', [$from,$to]);
            
            // If not admin, only export own data
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                $billsQuery->where('user_id', auth()->id());
                $expensesQuery->where('user_id', auth()->id());
            }
            
            $bills = $billsQuery->get();
            $expenses = $expensesQuery->get();

            $gross = $bills->sum('total_amount');
            $discount = $bills->sum('discount_amount');
            $net = $gross - $discount;

            $staffShare = 0;
            $annexShare = 0;
            $radiologistShare = 0;
            $radiographerShare = 0;
            foreach ($bills as $bill) {
                foreach ($bill->items as $item) {
                    $distribution = $item->revenueDistribution;
                    if ($distribution) {
                        $staffShare += $distribution->staff_amount;
                        $annexShare += $distribution->annex_amount;
                        $radiologistShare += $distribution->radiologist_amount;
                        $radiographerShare += $distribution->radiographer_amount;
                    }
                }
            }

            $totalExpense = $expenses->sum('amount');
            $includeSalary = auth()->check() && auth()->user()->role === 'admin';
            $salaryAmount = $this->salaryAmountForRange($from, $to, $includeSalary);
            $totalOutflow = $totalExpense + $salaryAmount;
            $profit = $annexShare - $totalOutflow;

            fputcsv($file, [$gross, $discount, $net, $staffShare, $annexShare, $radiologistShare, $radiographerShare, $totalExpense, $salaryAmount, $totalOutflow, $profit]);

            fclose($file);
        };

        return response()->stream($callback,200,$headers);
    }

    public function exportExcel(Request $request)
    {
        $from = $request->from;
        $to = $request->to;
        $reportId = 'ANNEX-' . now()->format('YmdHis');
        $isAdmin = $this->isAdmin();

        return Excel::download(
            new FinancialReportExport($from, $to, $reportId, $isAdmin),
            "financial-report-$reportId.xlsx",
            \Maatwebsite\Excel\Excel::XLSX,
            ['charts' => true]
        );
    }

   

    public function exportPdf(Request $request)
    {
        $from = $request->from;
        $to = $request->to;

        $billsQuery = Bill::with('items.revenueDistribution')->whereBetween('created_at', [$from,$to]);
        $expensesQuery = Expense::whereBetween('expense_date', [$from,$to]);
        
        // If not admin, only export own data
        if (!$this->isAdmin()) {
            $billsQuery->where('user_id', auth()->id());
            $expensesQuery->where('user_id', auth()->id());
        }
        
        $bills = $billsQuery->get();
        $expenses = $expensesQuery->get();

        $gross = $bills->sum('total_amount');
        $discount = $bills->sum('discount_amount');
        $net = $gross - $discount;

        // Calculate shares based on actual distributions
        $staffShare = 0;
        $annexShare = 0;
        $radiologistShare = 0;
        $radiographerShare = 0;
        foreach ($bills as $bill) {
            foreach ($bill->items as $item) {
                $distribution = $item->revenueDistribution;
                if ($distribution) {
                    $staffShare += $distribution->staff_amount;
                    $annexShare += $distribution->annex_amount;
                    $radiologistShare += $distribution->radiologist_amount;
                    $radiographerShare += $distribution->radiographer_amount;
                }
            }
        }

        $totalExpense = $expenses->sum('amount');
        $salaryAmount = $this->salaryAmountForRange($from, $to, $this->isAdmin());
        $totalOutflow = $totalExpense + $salaryAmount;
        $profit = $annexShare - $totalOutflow;

        $reportId = 'ANNEX-' . now()->format('YmdHis');
        $reporterName = auth()->user()->name;
        $reporterEmail = auth()->user()->email;
        $generatedDate = now()->format('d M Y');
        $generatedTime = now()->format('h:i A');
        $generatedReport = Report::create([
            'report_reference' => $reportId,
            'from_date' => $from,
            'to_date' => $to,
            'gross' => $gross,
            'discount' => $discount,
            'net' => $net,
            'staff_share' => $staffShare,
            'annex_share' => $annexShare,
            'radiologist_share' => $radiologistShare,
            'radiographer_share' => $radiographerShare,
            'expenses' => $totalExpense,
            'salary_amount' => $salaryAmount,
            'profit' => $profit,
            'user_id' => auth()->id(),
        ]);

       

        $chartImage = $request->chart_image;
        $logoPath = public_path('images/logo.png');

        $pdf = Pdf::loadView('admin.reports.pdf', compact(
            'gross','discount','net',
            'staffShare','annexShare',
            'radiologistShare','radiographerShare',
            'totalExpense',
            'salaryAmount',
            'totalOutflow',
            'profit',
            'from','to',
            'chartImage',
            'reportId',
            'logoPath',
            'reporterName',
            'reporterEmail',
            'generatedDate',
            'generatedTime',
            'reportId'
        ))->setPaper('a4', 'portrait');

        $fileName = "reports/$reportId.pdf";
        Storage::put($fileName, $pdf->output());

        $generatedReport->update([
            'pdf_path' => $fileName
        ]);

        return $pdf->download($reportId.'.pdf');
    }

    private function salaryAmountForRange($from, $to, bool $includeSalary): float
    {
        if (! $includeSalary || ! $from || ! $to) {
            return 0;
        }

        $fromMonth = Carbon::parse($from)->startOfMonth()->toDateString();
        $toMonth = Carbon::parse($to)->startOfMonth()->toDateString();

        return (float) SalaryPayment::whereBetween('salary_month', [$fromMonth, $toMonth])
            ->sum('amount');
    }
}
