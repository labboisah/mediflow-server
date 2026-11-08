<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\InvestigationRequest;
use App\Models\Bill;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = auth()->user()->department->investigationRequests()
            ->with(['patientVisit.patient.demographic', 'walkinPatient', 'requestedBy', 'performedBy', 'investigation'])
            ->orderByDesc('created_at');

        if ($request->ajax()) {
            $requestGroups = $requests->get()->groupBy(function ($request) {
                if ($request->patient_visit_id) {
                    return 'visit_'.$request->patient_visit_id;
                }

                if ($request->walkin_id) {
                    return 'walkin_'.$request->walkin_id;
                }

                return 'request_'.$request->id;
            })->map(function ($group, $groupKey) {
                $first = $group->first();
                $groupType = $first->patient_visit_id ? 'visit' : 'walkin';
                $groupId = $groupType === 'visit' ? $first->patient_visit_id : $first->walkin_id;
                $patientName = $first->patientVisit
                    ? $first->patientVisit->patient->demographic->full_name
                    : ($first->walkinPatient->name ?? 'Walkin Patient');
                $investigations = $group->pluck('investigation.name')->unique()->implode(', ');
                $completedAt = $group->filter(fn ($req) => $req->completed_at)->sortByDesc('completed_at')->first()?->completed_at;
                $requestedAt = $group->sortBy('created_at')->first()->created_at;
                $performedBy = $group->pluck('performedBy.name')->filter()->unique()->implode(', ');
                $status = $group->contains(fn ($req) => $req->status !== 'Completed') ? 'Pending' : 'Completed';
                $paymentStatus = $group->contains(fn ($req) => $req->payment_status !== 'paid') ? 'Payment not recorded' : 'Paid';
                $hasPendingResults = $group->where('payment_status', 'paid')->where('status', '!=', 'Completed')->isNotEmpty();
                $hasCompletedResults = $group->where('status', 'Completed')->isNotEmpty();

                return (object) [
                    'group_type' => $groupType,
                    'group_id' => $groupId,
                    'lab_no' => $first->getLabNo(),
                    'requested_by' => $first->requestedBy->name,
                    'patient_name' => $patientName,
                    'investigations' => $investigations,
                    'completed_at' => $completedAt,
                    'performed_by' => $performedBy,
                    'status' => $status,
                    'clinical_notes' => $group->pluck('clinical_diagnoses')->filter()->unique()->implode(', '),
                    'requested_at' => $requestedAt,
                    'payment_status' => $paymentStatus,
                    'has_pending_results' => $hasPendingResults,
                    'has_completed_results' => $hasCompletedResults,
                ];
            });

            $requestGroups = $requestGroups->sortByDesc('requested_at');
            $recordsTotal = $requestGroups->count();

            $search = trim(strtolower($request->input('search.value', '')));
            if ($search !== '') {
                $requestGroups = $requestGroups->filter(function ($group) use ($search) {
                    $haystack = strtolower(sprintf(
                        '%s %s %s %s %s %s',
                        $group->lab_no,
                        $group->requested_by,
                        $group->patient_name,
                        $group->investigations,
                        $group->status,
                        $group->payment_status
                    ));

                    return Str::contains($haystack, $search);
                });
            }

            $recordsFiltered = $requestGroups->count();
            $orderCol = (int) $request->input('order.0.column', 8);
            $orderDir = $request->input('order.0.dir', 'desc');
            $columns = [
                0 => 'lab_no',
                1 => 'requested_by',
                2 => 'patient_name',
                3 => 'investigations',
                4 => 'completed_at',
                5 => 'performed_by',
                6 => 'status',
                7 => 'clinical_notes',
                8 => 'requested_at',
                9 => null,
            ];

            $sortColumn = $columns[$orderCol] ?? 'requested_at';
            if ($sortColumn) {
                $requestGroups = $orderDir === 'asc'
                    ? $requestGroups->sortBy($sortColumn)
                    : $requestGroups->sortByDesc($sortColumn);
            }

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 10);
            $pageItems = $requestGroups->slice($start, $length)->values();

            $data = $pageItems->map(function ($group) {
                $actions = '';

                if ($group->group_id && $group->has_pending_results) {
                    $actions = '<a href="'.route('lab.requests.results.create', ['groupType' => $group->group_type, 'groupId' => $group->group_id]).'" class="btn btn-sm btn-outline-success mb-1"><i class="bi bi-send me-1"></i> Send Combined Result</a>';
                } elseif ($group->has_completed_results) {
                    $actions = '<a href="'.route('lab.requests.results.show', ['groupType' => $group->group_type, 'groupId' => $group->group_id]).'" class="btn btn-sm btn-outline-info mb-1"><i class="bi bi-eye me-1"></i> View Combined Results</a>';
                } else {
                    $actions = e($group->payment_status);
                }

                return [
                    $group->lab_no,
                    $group->requested_by,
                    $group->patient_name,
                    $group->investigations,
                    $group->completed_at,
                    $group->performed_by ?: 'N/A',
                    $group->status,
                    $group->clinical_notes,
                    $group->requested_at,
                    $actions,
                ];
            })->toArray();

            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        }

        $requestGroups = $requests->get()->groupBy(function ($request) {
            if ($request->patient_visit_id) {
                return 'visit_'.$request->patient_visit_id;
            }

            if ($request->walkin_id) {
                return 'walkin_'.$request->walkin_id;
            }

            return 'request_'.$request->id;
        })->map(function ($group, $groupKey) {
            $first = $group->first();
            $groupType = $first->patient_visit_id ? 'visit' : 'walkin';
            $groupId = $groupType === 'visit' ? $first->patient_visit_id : $first->walkin_id;
            $patientName = $first->patientVisit
                ? $first->patientVisit->patient->demographic->full_name
                : ($first->walkinPatient->name ?? 'Walkin Patient');
            $investigations = $group->pluck('investigation.name')->unique()->implode(', ');
            $completedAt = $group->filter(fn ($req) => $req->completed_at)->sortByDesc('completed_at')->first()?->completed_at;
            $requestedAt = $group->sortBy('created_at')->first()->created_at;
            $performedBy = $group->pluck('performedBy.name')->filter()->unique()->implode(', ');
            $status = $group->contains(fn ($req) => $req->status !== 'Completed') ? 'Pending' : 'Completed';
            $paymentStatus = $group->contains(fn ($req) => $req->payment_status !== 'paid') ? 'Payment not recorded' : 'Paid';
            $hasPendingResults = $group->where('payment_status', 'paid')->where('status', '!=', 'Completed')->isNotEmpty();
            $hasCompletedResults = $group->where('status', 'Completed')->isNotEmpty();

            return (object) [
                'group_type' => $groupType,
                'group_id' => $groupId,
                'lab_no' => $first->getLabNo(),
                'requested_by' => $first->requestedBy->name,
                'patient_name' => $patientName,
                'investigations' => $investigations,
                'completed_at' => $completedAt,
                'performed_by' => $performedBy,
                'status' => $status,
                'clinical_notes' => $group->pluck('clinical_diagnoses')->filter()->unique()->implode(', '),
                'requested_at' => $requestedAt,
                'payment_status' => $paymentStatus,
                'has_pending_results' => $hasPendingResults,
                'has_completed_results' => $hasCompletedResults,
            ];
        });

        $requestGroups = $requestGroups->sortByDesc('requested_at');
        return view('lab.request.index', compact('requestGroups'));
    }

    public function createResult(string $groupType, $groupId)
    {
        $query = InvestigationRequest::with([
            'investigation.parameters',
            'patientVisit.patient.demographic',
            'walkinPatient',
        ]);

        if ($groupType === 'visit') {
            $query->where('patient_visit_id', $groupId);
        } elseif ($groupType === 'walkin') {
            $query->where('walkin_id', $groupId);
        } else {
            abort(404);
        }

        $investigationRequests = $query
            ->whereHas('investigation.investigationType', fn ($type) => $type->where('department_id', auth()->user()->department_id))
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'Completed')
            ->get();

        if ($investigationRequests->isEmpty()) {
            return redirect()->route('lab.requests.index')->with('error', 'No paid pending investigation requests found for this patient.');
        }

        $patientName = $investigationRequests->first()->patientVisit
            ? $investigationRequests->first()->patientVisit->patient->demographic->full_name
            : ($investigationRequests->first()->walkinPatient->name ?? 'Walkin Patient');

        $hospitalNumber = $investigationRequests->first()->patientVisit
            ? $investigationRequests->first()->patientVisit->patient->hospital_number
            : null;

        return view('lab.request.create-result', compact(
            'investigationRequests',
            'groupType',
            'groupId',
            'patientName',
            'hospitalNumber'
        ));
    }

    public function storeResult(Request $request, string $groupType, $groupId)
    {
        $validated = $request->validate([
            'parameters' => 'required|array',
            'parameters.*' => 'array',
            'parameters.*.*' => 'nullable|string',
        ]);

        $query = InvestigationRequest::with('patientVisit', 'investigation.parameters');

        if ($groupType === 'visit') {
            $query->where('patient_visit_id', $groupId);
        } elseif ($groupType === 'walkin') {
            $query->where('walkin_id', $groupId);
        } else {
            abort(404);
        }

        $investigationRequests = $query
            ->whereHas('investigation.investigationType', fn ($type) => $type->where('department_id', auth()->user()->department_id))
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'Completed')
            ->get();

        foreach ($investigationRequests as $investigationRequest) {
            $requestParameters = $validated['parameters'][$investigationRequest->id] ?? [];
            $investigationRequest->loadMissing('investigation.parameters');
            $allowedParameterIds = $investigationRequest->investigation->parameters->pluck('id')->map(fn ($id) => (string) $id)->all();
            foreach (array_keys($requestParameters) as $parameterId) {
                abort_unless(in_array((string) $parameterId, $allowedParameterIds, true), 422, 'A result parameter does not belong to this investigation.');
            }
            if ($allowedParameterIds && collect($allowedParameterIds)->contains(fn ($id) => trim((string) ($requestParameters[$id] ?? '')) === '')) {
                return back()->withInput()->with('error', 'Complete every configured laboratory parameter before submitting results.');
            }
            $hasResult = false;

            foreach ($requestParameters as $parameterId => $value) {
                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                $investigationRequest->investigationResults()->updateOrCreate(
                    ['parameter_id' => $parameterId],
                    ['value' => $value]
                );

                $hasResult = true;
            }

            if ($hasResult) {
                $investigationRequest->update([
                    'performed_by' => auth()->id(),
                    'completed_at' => now(),
                    'status' => 'Completed',
                ]);

                if ($investigationRequest->patientVisit) {
                    $investigationRequest->patientVisit->visitActivities()->create([
                        'activity' => "Lab results recorded for {$investigationRequest->investigation->name}",
                        'recorded_by' => auth()->id(),
                    ]);
                }
            }
        }

        return redirect()->route('lab.requests.index')->with('success', 'Investigation results recorded successfully.');
    }

    public function showResult(Bill $bill)
    {
        $bill->load([
            'patientVisit.patient.demographic',
            'walkinPatient',
            'investigationRequests.investigation',
            'investigationRequests.investigationResults.parameter',
        ]);

        return view('lab.request.result', compact('bill'));
    }
}




