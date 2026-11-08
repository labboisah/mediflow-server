<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillResult;
use App\Models\ResultTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffResultController extends Controller
{
    public function dashboard()
    {
        $dateColumn = $this->activityDateColumn();

        $todayResults = $this->activityQuery()
            ->whereDate($dateColumn, today())
            ->count();

        $monthResults = $this->activityQuery()
            ->whereMonth($dateColumn, now()->month)
            ->whereYear($dateColumn, now()->year)
            ->count();

        $monthCommission = $this->commissionQuery()
            ->whereMonth($dateColumn, now()->month)
            ->whereYear($dateColumn, now()->year)
            ->sum($this->commissionColumn());

        $recentResults = $this->activityQuery()
            ->with('bill', 'billItem.service')
            ->latest($dateColumn)
            ->limit(5)
            ->get();

        return view('staff.dashboard', compact(
            'todayResults',
            'monthResults',
            'monthCommission',
            'recentResults'
        ));
    }

    public function index()
    {
        return view('staff.results.index');
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'bill_no' => 'required|string',
        ]);

        $bill = Bill::where('bill_no', $request->bill_no)->first();

        if (! $bill) {
            return back()->withInput()->withErrors(['bill_no' => 'Bill number was not found.']);
        }

        if (! $this->billIsPaid($bill)) {
            return back()->withInput()->withErrors(['bill_no' => 'Only fully paid bills are allowed for result entry.']);
        }

        return redirect()->route('staff.results.entry', $bill);
    }

    public function entry(Bill $bill)
    {
        $this->ensurePaidBill($bill);

        $bill->load([
            'items.service.category',
            'items.result.performer',
            'items.result.reporter',
            'user',
            'payments.user',
        ]);

        $templates = ResultTemplate::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
        $templatePayload = $templates->mapWithKeys(fn ($template) => [
            $template->id => [
                'clinical_note' => $template->clinical_note,
                'findings' => $template->findings,
                'impression' => $template->impression,
            ],
        ])->toArray();

        $staffType = auth()->user()->staff_type ?? 'staff';

        return view('staff.results.entry', compact('bill', 'templates', 'templatePayload', 'staffType'));
    }

    public function store(Request $request, Bill $bill)
    {
        $this->ensurePaidBill($bill);

        $validated = $request->validate([
            'bill_item_id' => 'required|exists:bill_items,id',
            'clinical_note' => 'nullable|string',
            'findings' => auth()->user()->staff_type === 'radiographer' ? 'nullable|string' : 'required|string',
            'impression' => 'nullable|string',
            'save_template' => 'nullable|boolean',
            'template_name' => 'required_if:save_template,1|nullable|string|max:255',
        ]);

        $billItem = $bill->items()->whereKey($validated['bill_item_id'])->firstOrFail();
        $staffType = auth()->user()->staff_type ?? 'staff';

        $existingResult = BillResult::where('bill_item_id', $billItem->id)->first();

        if ($staffType === 'radiographer') {
            if ($existingResult?->performed_by && $existingResult->performed_by !== auth()->id()) {
                abort(403, 'This investigation has already been performed by another radiographer.');
            }

            BillResult::updateOrCreate(
                ['bill_item_id' => $billItem->id],
                [
                    'bill_id' => $bill->id,
                    'staff_id' => $existingResult?->staff_id ?? auth()->id(),
                    'performed_by' => auth()->id(),
                    'performed_at' => now(),
                    'findings' => $existingResult?->findings ?? '',
                    'status' => $existingResult?->reported_at ? 'completed' : 'performed',
                ]
            );

            return redirect()
                ->route('staff.results.entry', $bill)
                ->with('success', 'Investigation marked as performed.');
        }

        if ($existingResult && $existingResult->reported_by && $existingResult->reported_by !== auth()->id()) {
            abort(403, 'This investigation already has a report entered by another staff member.');
        }

        $result = BillResult::updateOrCreate(
            ['bill_item_id' => $billItem->id],
            [
                'bill_id' => $bill->id,
                'staff_id' => auth()->id(),
                'reported_by' => auth()->id(),
                'clinical_note' => $validated['clinical_note'] ?? null,
                'findings' => $validated['findings'],
                'impression' => $validated['impression'] ?? null,
                'status' => 'completed',
                'reported_at' => now(),
                'completed_at' => now(),
            ]
        );

        if ($request->boolean('save_template')) {
            ResultTemplate::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'name' => $validated['template_name'],
                ],
                [
                    'clinical_note' => $validated['clinical_note'] ?? null,
                    'findings' => $validated['findings'],
                    'impression' => $validated['impression'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('staff.results.print', $result)
            ->with('success', 'Result saved successfully.');
    }

    public function reports(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $dateColumn = $this->activityDateColumn();

        $results = $this->activityQuery()
            ->with('bill', 'billItem.service', 'performer', 'reporter', 'staff')
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->latest($dateColumn)
            ->paginate(15)
            ->withQueryString();

        $staffType = auth()->user()->staff_type ?? 'staff';

        return view('staff.results.reports', compact('results', 'from', 'to', 'staffType'));
    }

    public function commission(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $commissionColumn = $this->commissionColumn();
        $dateColumn = $this->activityDateColumn();
        $staffType = auth()->user()->staff_type ?? 'staff';

        $results = $this->commissionQuery()
            ->select([
                'bill_results.*',
                'bill_items.price as bill_amount',
                $commissionColumn . ' as commission_amount',
            ])
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->with('bill', 'billItem.service')
            ->latest($dateColumn)
            ->paginate(15)
            ->withQueryString();

        $todayCommission = $this->commissionQuery()
            ->whereDate($dateColumn, today())
            ->sum($commissionColumn);

        $monthCommission = $this->commissionQuery()
            ->whereMonth($dateColumn, now()->month)
            ->whereYear($dateColumn, now()->year)
            ->sum($commissionColumn);

        $filteredCommission = $this->commissionQuery()
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->sum($commissionColumn);

        $filteredBillAmount = $this->commissionQuery()
            ->when($from, fn ($query) => $query->whereDate($dateColumn, '>=', $from))
            ->when($to, fn ($query) => $query->whereDate($dateColumn, '<=', $to))
            ->sum('bill_items.price');

        return view('staff.results.commission', compact(
            'results',
            'todayCommission',
            'monthCommission',
            'filteredCommission',
            'filteredBillAmount',
            'from',
            'to',
            'staffType'
        ));
    }

    public function print(BillResult $result)
    {
        if (($result->reported_by ?? $result->staff_id) !== auth()->id()) {
            abort(403);
        }

        $result->load([
            'bill.user',
            'bill.payments.user',
            'billItem.service.category',
            'performer',
            'reporter',
            'staff',
        ]);

        return view('staff.results.print', compact('result'));
    }

    private function ensurePaidBill(Bill $bill): void
    {
        if (! $this->billIsPaid($bill)) {
            abort(403, 'Only fully paid bills are allowed for result entry.');
        }
    }

    private function billIsPaid(Bill $bill): bool
    {
        return $bill->payment_status === 'paid' || (float) $bill->balance <= 0;
    }

    private function commissionQuery()
    {
        return $this->activityQuery()
            ->join('bill_items', 'bill_results.bill_item_id', '=', 'bill_items.id')
            ->leftJoin('revenue_distributions', 'bill_items.id', '=', 'revenue_distributions.bill_item_id');
    }

    private function activityQuery()
    {
        return BillResult::query()->where(function ($query) {
            if ((auth()->user()->staff_type ?? 'staff') === 'radiographer') {
                $query->where('bill_results.performed_by', auth()->id());
                return;
            }

            $query->where('bill_results.reported_by', auth()->id())
                ->orWhere(function ($fallback) {
                    $fallback->whereNull('bill_results.reported_by')
                        ->where('bill_results.staff_id', auth()->id());
                });
        });
    }

    private function commissionColumn(): string
    {
        return match (auth()->user()->staff_type ?? 'staff') {
            'radiologist' => 'revenue_distributions.radiologist_amount',
            'radiographer' => 'revenue_distributions.radiographer_amount',
            default => 'revenue_distributions.staff_amount',
        };
    }

    private function activityDateColumn(): string
    {
        return (auth()->user()->staff_type ?? 'staff') === 'radiographer'
            ? 'bill_results.performed_at'
            : 'bill_results.reported_at';
    }

    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
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
        ];
    }
}
