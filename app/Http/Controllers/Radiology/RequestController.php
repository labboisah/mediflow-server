<?php

namespace App\Http\Controllers\Radiology;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticReport;
use App\Models\DiagnosticReportRevision;
use App\Models\InvestigationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RequestController extends Controller
{
    public function index()
    {
        $department = auth()->user()->department;
        abort_unless($department, 403);
        $requests = $department->investigationRequests()->with(['bill','patientVisit.patient.demographic','walkinPatient','requestedBy','performedBy','investigation'])
            ->orderByDesc('created_at')->get()->filter(fn ($record) => $record->bill !== null);
        return view('radiology.request.index', compact('requests'));
    }

    public function markPerformed(InvestigationRequest $investigationRequest)
    {
        $this->authorizeRequest($investigationRequest);
        abort_unless(auth()->user()->hasRole('radiographer'), 403);
        abort_unless($investigationRequest->payment_status === 'paid', 403, 'Payment is required before diagnostic work.');
        abort_if($investigationRequest->diagnosticReport?->performed_at, 409, 'This request has already been marked performed.');
        abort_if($investigationRequest->diagnosticReport?->reported_at, 409, 'A finalized report cannot be marked performed again.');

        DB::transaction(function () use ($investigationRequest) {
            $report = DiagnosticReport::firstOrNew(['investigation_request_id' => $investigationRequest->id]);
            $report->fill(['status' => 'performed', 'performed_by' => auth()->id(), 'performed_at' => now()]);
            $report->save();
            $investigationRequest->update(['performed_by' => auth()->id()]);
            $this->snapshot($report, 'performed');
        });

        return back()->with('success', 'Radiology work marked performed. It is now ready for radiologist reporting.');
    }

    public function createResult(InvestigationRequest $investigationRequest)
    {
        $this->authorizeRequest($investigationRequest);
        abort_unless(auth()->user()->hasRole('radiologist'), 403);
        abort_unless($investigationRequest->payment_status === 'paid', 403, 'Payment is required before reporting.');
        abort_unless($investigationRequest->diagnosticReport?->performed_at, 409, 'A radiographer must mark this request performed first.');
        $investigationRequest->load(['investigation.parameters', 'diagnosticReport']);
        return view('radiology.request.create-result', compact('investigationRequest'));
    }

    public function storeResult(Request $request, InvestigationRequest $investigationRequest)
    {
        $this->authorizeRequest($investigationRequest);
        abort_unless(auth()->user()->hasRole('radiologist'), 403);
        abort_unless($investigationRequest->payment_status === 'paid' && $investigationRequest->diagnosticReport?->performed_at, 403);
        $validated = $request->validate([
            'clinical_note' => 'nullable|string|max:10000', 'findings' => 'required|string|max:30000',
            'impression' => 'required|string|max:12000', 'parameters' => 'sometimes|array',
            'parameters.*' => 'nullable|string|max:10000', 'result_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);
        $this->saveParameters($investigationRequest, $validated['parameters'] ?? []);
        DB::transaction(function () use ($request, $validated, $investigationRequest) {
            $report = DiagnosticReport::firstOrNew(['investigation_request_id' => $investigationRequest->id]);
            $report->fill(['clinical_note' => $validated['clinical_note'] ?? null, 'findings' => $validated['findings'], 'impression' => $validated['impression'], 'status' => 'reported', 'reported_by' => auth()->id(), 'reported_at' => now(), 'version' => $report->version + 1]);
            $report->save();
            if ($request->hasFile('result_image')) {
                if ($investigationRequest->result_image) Storage::disk('local')->delete($investigationRequest->result_image);
                Storage::disk('public')->delete($investigationRequest->result_image);
                $investigationRequest->result_image = $request->file('result_image')->store('radiology-results', 'local');
            }
            $investigationRequest->update(['status' => 'Completed', 'completed_at' => now(), 'result_image' => $investigationRequest->result_image]);
            $this->snapshot($report, $report->wasRecentlyCreated ? 'reported' : 'amended');
            $investigationRequest->setRelation('diagnosticReport', $report);
            app(\App\Services\Diagnostics\BookDiagnosticRevenue::class)->forRequest($investigationRequest);
        });
        return redirect()->route('radiology.requests.show', $investigationRequest)->with('success', 'Radiologist report saved.');
    }

    public function image(InvestigationRequest $investigationRequest)
    {
        $this->authorizeRequest($investigationRequest);
        abort_unless($investigationRequest->result_image, 404);
        if (Storage::disk('local')->exists($investigationRequest->result_image)) return Storage::disk('local')->response($investigationRequest->result_image);
        abort_unless(Storage::disk('public')->exists($investigationRequest->result_image), 404);
        return Storage::disk('public')->response($investigationRequest->result_image);
    }

    public function show(InvestigationRequest $investigationRequest)
    {
        $this->authorizeRequest($investigationRequest);
        $investigationRequest->load(['investigation.parameters','investigationResults.parameter','diagnosticReport.revisions.author','patientVisit.patient.demographic','walkinPatient','requestedBy','performedBy']);
        $patientName = $investigationRequest->patientVisit?->patient?->demographic?->full_name ?? $investigationRequest->walkinPatient?->name ?? 'Walk-in Patient';
        $hospitalNumber = $investigationRequest->patientVisit?->patient?->hospital_number;
        return view('radiology.request.result', compact('investigationRequest', 'patientName', 'hospitalNumber'));
    }

    public function editResult(InvestigationRequest $investigationRequest)
    {
        $this->authorizeRequest($investigationRequest);
        abort_unless(auth()->user()->hasRole('radiologist'), 403);
        $investigationRequest->load(['investigation.parameters','investigationResults.parameter','diagnosticReport']);
        return view('radiology.request.edit-result', compact('investigationRequest'));
    }

    public function updateResult(Request $request, InvestigationRequest $investigationRequest)
    {
        return $this->storeResult($request, $investigationRequest);
    }

    private function authorizeRequest(InvestigationRequest $request): void
    {
        $departmentId = auth()->user()->department_id;
        abort_unless($departmentId && $request->investigation?->investigationType?->department_id === $departmentId, 403);
    }

    private function saveParameters(InvestigationRequest $request, array $parameters): void
    {
        $allowed = $request->investigation->parameters()->pluck('id')->map(fn ($id) => (string) $id)->all();
        foreach ($parameters as $id => $value) {
            abort_unless(in_array((string) $id, $allowed, true), 422, 'A result parameter does not belong to this investigation.');
            if ($value !== null && trim($value) !== '') $request->investigationResults()->updateOrCreate(['parameter_id' => $id], ['value' => $value]);
        }
    }

    private function snapshot(DiagnosticReport $report, string $action): void
    {
        DiagnosticReportRevision::create(['diagnostic_report_id' => $report->id, 'version' => $report->version, 'action' => $action, 'clinical_note' => $report->clinical_note, 'findings' => $report->findings, 'impression' => $report->impression, 'status' => $report->status, 'changed_by' => auth()->id(), 'changed_at' => now()]);
    }
}




