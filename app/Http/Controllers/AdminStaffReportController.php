<?php

namespace App\Http\Controllers;

use App\Models\BillResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminStaffReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $staffId] = $this->filters($request);

        $staffMembers = User::where('role', 'staff')->orderBy('name')->get();
        $results = $this->baseQuery($from, $to, $staffId)
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        $results->getCollection()->transform(function (BillResult $result) {
            $result->bill_amount = $this->billAmountForResult($result);
            $result->commission_amount = $this->commissionForDetailedResult($result);
            return $result;
        });

        $summary = $this->buildSummary($from, $to, $staffId);
        $totalResults = $summary->sum('total_results');
        $totalBillAmount = $summary->sum('total_bill_amount');
        $totalCommission = $summary->sum('total_commission');

        return view('admin.staff-reports.index', compact(
            'staffMembers',
            'results',
            'summary',
            'totalResults',
            'totalBillAmount',
            'totalCommission',
            'from',
            'to',
            'staffId'
        ));
    }

    public function download(Request $request)
    {
        [$from, $to, $staffId] = $this->filters($request);
        $summary = $this->buildSummary($from, $to, $staffId);
        $filename = 'staff-work-summary-' . now()->format('YmdHis') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($summary, $from, $to) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Staff Work Summary']);
            fputcsv($file, ['From', $from ?: 'All time', 'To', $to ?: 'All time']);
            fputcsv($file, []);
            fputcsv($file, ['Performed By', 'Staff Type', 'Total Results', 'Total Bill Amount', 'Total Commission']);

            foreach ($summary as $row) {
                fputcsv($file, [
                    $row['staff_name'],
                    $row['staff_type'],
                    $row['total_results'],
                    $row['total_bill_amount'],
                    $row['total_commission'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function baseQuery(?string $from, ?string $to, ?string $staffId)
    {
        return BillResult::with([
                'bill',
                'billItem.service',
                'billItem.revenueDistribution',
                'staff',
                'performer',
                'reporter',
            ])
            ->when($from, function ($query) use ($from) {
                $query->where(function ($dateQuery) use ($from) {
                    $dateQuery->whereDate('performed_at', '>=', $from)
                        ->orWhereDate('reported_at', '>=', $from)
                        ->orWhereDate('completed_at', '>=', $from);
                });
            })
            ->when($to, function ($query) use ($to) {
                $query->where(function ($dateQuery) use ($to) {
                    $dateQuery->whereDate('performed_at', '<=', $to)
                        ->orWhereDate('reported_at', '<=', $to)
                        ->orWhereDate('completed_at', '<=', $to);
                });
            })
            ->when($staffId, function ($query) use ($staffId) {
                $query->where(function ($staffQuery) use ($staffId) {
                    $staffQuery->where('performed_by', $staffId)
                        ->orWhere('reported_by', $staffId)
                        ->orWhere('staff_id', $staffId);
                });
            });
    }

    private function buildSummary(?string $from, ?string $to, ?string $staffId)
    {
        return $this->baseQuery($from, $to, $staffId)
            ->get()
            ->flatMap(function (BillResult $result) {
                $rows = collect();

                if ($result->performer) {
                    $rows->push($this->summaryRow($result, $result->performer, 'radiographer'));
                }

                $reporter = $result->reporter ?? $result->staff;
                if ($reporter) {
                    $rows->push($this->summaryRow($result, $reporter, $reporter->staff_type ?? 'staff'));
                }

                return $rows;
            })
            ->groupBy('staff_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'staff_id' => $first['staff_id'],
                    'staff_name' => $first['staff_name'],
                    'staff_type' => $first['staff_type'],
                    'total_results' => $rows->count(),
                    'total_bill_amount' => $rows->sum('bill_amount'),
                    'total_commission' => $rows->sum('commission_amount'),
                ];
            })
            ->sortBy('staff_name')
            ->values();
    }

    private function summaryRow(BillResult $result, User $staff, string $staffType): array
    {
        return [
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'staff_type' => strtoupper($staffType),
            'bill_amount' => $this->billAmountForResult($result),
            'commission_amount' => $this->commissionForStaffType($result, $staffType),
        ];
    }

    private function commissionForDetailedResult(BillResult $result): float
    {
        if ($result->performer && $result->reporter && $result->performer->id !== $result->reporter->id) {
            return $this->commissionForStaffType($result, 'radiographer')
                + $this->commissionForStaffType($result, $result->reporter->staff_type ?? 'staff');
        }

        $staff = $result->reporter ?? $result->performer ?? $result->staff;

        return $this->commissionForStaffType($result, $staff?->staff_type ?? 'staff');
    }

    private function commissionForStaffType(BillResult $result, string $staffType): float
    {
        $distribution = $result->billItem?->revenueDistribution;

        if (! $distribution) {
            return 0;
        }

        return (float) match ($staffType) {
            'radiologist' => $distribution->radiologist_amount,
            'radiographer' => $distribution->radiographer_amount,
            default => $distribution->staff_amount,
        };
    }

    private function billAmountForResult(BillResult $result): float
    {
        return (float) ($result->billItem?->price ?? 0);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'staff_id' => 'nullable|exists:users,id',
        ]);

        if (
            ! empty($validated['from'])
            && ! empty($validated['to'])
            && $validated['to'] < $validated['from']
        ) {
            throw ValidationException::withMessages([
                'to' => 'The to date must be after or equal to the from date.',
            ]);
        }

        return [
            $validated['from'] ?? null,
            $validated['to'] ?? null,
            $validated['staff_id'] ?? null,
        ];
    }
}
