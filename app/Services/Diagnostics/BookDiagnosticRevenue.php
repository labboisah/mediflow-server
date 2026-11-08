<?php

namespace App\Services\Diagnostics;

use App\Models\DiagnosticRevenueDistribution;
use App\Models\DiagnosticRevenueRule;
use App\Models\InvestigationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookDiagnosticRevenue
{
    public function forRequest(InvestigationRequest $request): DiagnosticRevenueDistribution
    {
        $line = $request->bill?->billInvestigations()->where('investigation_id', $request->investigation_id)->first();
        if (! $line) throw ValidationException::withMessages(['commission' => 'This diagnostic request has no matching billed investigation line.']);
        return DB::transaction(function () use ($line, $request) {
            if ($existing = DiagnosticRevenueDistribution::where('bill_investigation_id', $line->id)->first()) return $existing;
            $rule = DiagnosticRevenueRule::where('investigation_type_id', $request->investigation->investigation_type_id)->where('is_active', true)->first();
            $parts = ['staff_percent' => (float) ($rule->staff_percent ?? 0), 'radiographer_percent' => (float) ($rule->radiographer_percent ?? 0), 'radiologist_percent' => (float) ($rule->radiologist_percent ?? 0), 'center_percent' => (float) ($rule->center_percent ?? 100)];
            if (round(array_sum($parts), 2) !== 100.0) throw ValidationException::withMessages(['commission' => 'Diagnostic revenue rule percentages must total 100%.']);
            $base = (float) $line->subtotal;
            $amounts = [];
            foreach ($parts as $field => $percent) $amounts[str_replace('_percent', '_amount', $field)] = round($base * $percent / 100, 2);
            $amounts['center_amount'] += round($base - array_sum($amounts), 2);
            return DiagnosticRevenueDistribution::create(['bill_investigation_id' => $line->id, 'diagnostic_revenue_rule_id' => $rule?->id, 'base_amount' => $base, ...$parts, ...$amounts, 'performed_by' => $request->diagnosticReport?->performed_by, 'reported_by' => $request->diagnosticReport?->reported_by, 'booked_at' => now()]);
        });
    }
}

