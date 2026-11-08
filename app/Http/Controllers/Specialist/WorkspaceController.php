<?php

namespace App\Http\Controllers\Specialist;

use App\Http\Controllers\Controller;
use App\Models\CollaborationPartner;
use App\Models\FileType;
use App\Models\Investigation;
use App\Models\InvestigationRequest;
use App\Models\InvestigationResult;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientDemographic;
use App\Models\PatientReferral;
use App\Models\PaymentMethod;
use App\Models\Route as MedicineRoute;
use App\Models\SpecialistDocument;
use App\Models\SpecialistProfile;
use App\Models\SpecialistService;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\SpecialistAccess;
use App\Services\SpecialistWorkflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    public function __construct(private SpecialistAccess $access, private SpecialistWorkflow $workflow) {}

    public function index(Request $r)
    {
        $u = $this->access->require('specialist_appointment.read');
        $q = $this->access->consultations($u);
        $counts = ['scheduled' => (clone $q)->where('status', 'scheduled')->count(), 'in_progress' => (clone $q)->where('status', 'in_progress')->count(),
            'followup' => (clone $q)->whereNotNull('followup_due')->whereNull('followup_consultation_id')->whereDate('followup_due', '<=', today())->count()];
        $ids = (clone $q)->select('specialist_consultations.id');
        if ($u->hasPermission('specialist_consultation.read')) {
            $counts['pending_results'] = InvestigationRequest::whereIn('specialist_consultation_id', $ids)->whereNull('completed_at')->count();
            $counts['results_to_review'] = InvestigationRequest::whereIn('specialist_consultation_id', $ids)->whereNotNull('completed_at')->whereNull('reviewed_at')->count();
            $counts['active_referrals'] = PatientReferral::whereIn('specialist_consultation_id', $ids)->whereIn('status', ['Pending', 'Accepted'])->count();
        }
        $search = trim((string) $r->query('search', ''));
        $patients = $this->access->patients($u)->with('demographic')->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
            $q->where('hospital_number', 'like', "%{$search}%")->orWhereHas('demographic', fn ($q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
        }))->latest()->limit(30)->get();

        return view('specialist.index', [
            'consultations' => $q->with('patient.demographic', 'profile.user', 'appointment')->latest()->paginate(20),
            'counts' => $counts, 'patients' => $patients, 'profiles' => SpecialistProfile::with('user')->where('is_active', true)->get(),
            'offerings' => SpecialistService::with('service', 'specialty')->whereHas('service', fn ($q) => $q->where('is_active', true))->get(),
            'fileTypes' => FileType::all(), 'search' => $search,
            'incoming' => PatientReferral::with('patient.demographic')->where('destination_user_id', $u->id)->whereNotNull('specialist_consultation_id')->whereIn('status', ['Pending', 'Accepted'])->latest()->get(),
        ]);
    }

    public function register(Request $r)
    {
        $u = $this->access->require('specialist_patient.manage');
        $d = $r->validate(['first_name' => ['required', 'string', 'max:255'], 'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])], 'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'phone_number' => ['required', 'string', 'max:30', 'unique:patient_demographics,phone_number'],
            'file_type_id' => ['required', 'integer', 'exists:file_types,id'], 'email' => ['nullable', 'email', 'max:255', 'unique:patient_demographics,email']]);
        DB::transaction(function () use ($d, $u) {
            // A shared identity, with a collision-resistant identifier; no duplicate clinical registry.
            $patient = Patient::create(['file_type_id' => $d['file_type_id'], 'hospital_number' => 'SP'.Str::ulid(), 'registration_date' => now()]);
            PatientDemographic::create(collect($d)->except('file_type_id')->all() + ['patient_id' => $patient->id]);
            $this->access->grant($patient->id, $u->id);
            $this->workflow->audit('patient_registered', $patient);
        });

        return back()->with('success', 'Patient registered. Book a consultation below.');
    }

    public function assign(Request $r)
    {
        $this->access->require('specialist_patient.assign');
        $d = $r->validate(['hospital_number' => ['required', 'string'], 'user_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')]]);
        $patient = Patient::where('hospital_number', $d['hospital_number'])->firstOrFail();
        $recipient = User::findOrFail($d['user_id']);
        abort_unless($recipient->hasPermission('specialist_appointment.read'), 422, 'Recipient needs Specialist workspace permission.');
        $this->access->grant($patient->id, $recipient->id);
        $this->workflow->audit('patient_access_granted', $patient, ['user_id' => $recipient->id]);

        return back()->with('success', 'Patient access assigned.');
    }

    public function revoke(Request $r)
    {
        $this->access->require('specialist_patient.assign');
        $d = $r->validate(['patient_id' => ['required', 'integer'], 'user_id' => ['required', 'integer']]);
        DB::transaction(function () use ($d) {
            $patient = Patient::findOrFail($d['patient_id']);
            DB::table('specialist_patient_access')->where('patient_id', $d['patient_id'])->where('user_id', $d['user_id'])->delete();
            $this->workflow->audit('patient_access_revoked', $patient, ['user_id' => $d['user_id']]);
        });

        return back()->with('success', 'Patient access revoked.');
    }

    public function book(Request $r)
    {
        $c = $this->workflow->book($r->all());

        return redirect()->route('specialist.index')->with('success', 'Appointment booked for '.$c->appointment->appointment_date->format('Y-m-d').'.');
    }

    public function show(int $consultation)
    {
        $c = $this->access->consultation($consultation, 'specialist_consultation.read');
        $c->load('patient.demographic', 'profile.user', 'appointment', 'bill.payments.paymentMethod', 'prescriptions.prescriptionItems.medicine', 'prescriptions.prescriptionItems.route',
            'investigations.investigation', 'referrals', 'amendments', 'carePlans', 'documents');
        $this->workflow->audit('record_viewed', $c);

        return view('specialist.consultation', [
            'c' => $c, 'medicines' => Medicine::orderBy('name')->get(), 'routes' => MedicineRoute::orderBy('name')->get(),
            'tests' => Investigation::with('investigationType.department')->orderBy('name')->get(), 'partners' => CollaborationPartner::where('is_active', true)->get(),
            'receivers' => User::whereHas('roles.permissions', fn ($q) => $q->where('name', 'specialist_referral.accept'))->orderBy('name')->get(),
            'methods' => PaymentMethod::where('is_active', true)->get(),
            'history' => $this->access->consultations(auth()->user())->where('patient_id', $c->patient_id)->where('id', '!=', $c->id)->with('profile.user')->latest()->get(),
            'results' => InvestigationResult::with('parameter')->whereIn('investigation_request_id', $c->investigations->pluck('id'))->get()->groupBy('investigation_request_id'),
        ]);
    }

    public function action(Request $r, int $consultation, string $action)
    {
        match ($action) {
            'start' => $this->workflow->start($consultation),
            'clinical' => $this->workflow->saveClinical($consultation, $r->all()),
            'reschedule' => $this->workflow->reschedule($consultation, $r->all()),
            'cancel' => $this->workflow->cancel($consultation, (string) $r->input('reason')),
            'amend' => $this->workflow->amend($consultation, $r->all()),
            'care-plan' => $this->workflow->carePlan($consultation, $r->all()),
            'bill' => $this->workflow->bill($consultation),
            'pay' => $this->workflow->pay($consultation, $r->all()),
            'prescribe' => $this->workflow->prescribe($consultation, $r->all()),
            'investigate' => $this->workflow->investigate($consultation, $r->all()),
            'refer' => $this->workflow->refer($consultation, $r->all()),
            'external-outcome' => $this->workflow->externalOutcome($consultation, (int) $r->input('referral_id'), $r->all()),
            'review' => $this->workflow->reviewResult($consultation, (int) $r->input('request_id'), (string) $r->input('summary')),
            default => abort(404),
        };

        return back()->with('success', 'Saved successfully.');
    }

    public function respond(Request $r, int $referral)
    {
        $this->workflow->respondReferral($referral, $r->all());

        return back()->with('success', 'Referral response saved.');
    }

    public function upload(Request $r, int $consultation)
    {
        $c = $this->access->consultation($consultation, 'specialist_result.review');
        $d = $r->validate(['document' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
            'source' => ['required', 'string', 'max:255'], 'report_date' => ['required', 'date', 'before_or_equal:today'], 'request_id' => ['required', 'integer']]);
        $order = $c->investigations()->where('is_external', true)->whereNull('reviewed_at')->findOrFail($d['request_id']);
        abort_if($order->routing_method === 'partner', 422, 'Use the Care Network case for partner documents and review.');
        $file = $r->file('document');
        $path = $file->store('specialist/reports', 'local');
        abort_unless($path, 503, 'The report could not be stored. Please retry.');
        try {
            DB::transaction(function () use ($c, $order, $d, $file, $path) {
                InvestigationRequest::whereKey($order->id)->whereNull('reviewed_at')->lockForUpdate()->firstOrFail();
                $doc = $c->documents()->create(['investigation_request_id' => $order->id, 'uploaded_by' => auth()->id(), 'path' => $path,
                    'name' => mb_substr($file->getClientOriginalName(), 0, 255), 'mime' => $file->getMimeType(), 'size' => $file->getSize(),
                    'source' => $d['source'], 'report_date' => $d['report_date']]);
                $order->update(['status' => 'Result available', 'completed_at' => now()]);
                $this->workflow->audit('external_report_uploaded', $doc);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return back()->with('success', 'External report attached; clinician review is still required.');
    }

    public function document(int $document)
    {
        $doc = SpecialistDocument::findOrFail($document);
        $this->access->consultation($doc->specialist_consultation_id, 'specialist_result.read');
        $this->workflow->audit('document_downloaded', $doc);

        return Storage::disk('local')->download($doc->path, $doc->name, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function print(int $consultation, string $kind, int $record)
    {
        $permission = $kind === 'receipt' ? 'specialist_billing.read' : 'specialist_consultation.read';
        $c = $this->access->consultation($consultation, $permission);
        $item = match ($kind) {
            'prescription' => $c->prescriptions()->with('prescriptionItems.route')->findOrFail($record),
            'investigation' => $c->investigations()->findOrFail($record),
            'referral' => $c->referrals()->findOrFail($record),
            'receipt' => $c->bill?->payments()->with('paymentMethod')->findOrFail($record),
            default => abort(404),
        };
        abort_unless($item, 404);
        $this->workflow->audit('document_printed', $c, ['kind' => $kind, 'record_id' => $record]);

        return response()->view('specialist.print', compact('c', 'kind', 'item'))->header('Cache-Control', 'private, no-store');
    }

    public function history(int $patient)
    {
        $user = $this->access->require('specialist_consultation.read');
        $patient = $this->access->patients($user)->findOrFail($patient);
        $visits = $patient->visits()->with('continuations.writtenBy', 'prescriptions.prescriptionItems.medicine', 'investigationRequests.investigation', 'vitalSigns', 'admissions.discharge')->latest()->paginate(15);
        $consultations = $this->access->consultations($user)->where('patient_id', $patient->id)->with('profile.user', 'amendments', 'carePlans')->latest()->get();
        $this->workflow->audit('patient_history_viewed', $patient);

        return view('specialist.history', compact('patient', 'visits', 'consultations'));
    }

    public function archive(int $patient)
    {
        $user = auth()->user();
        abort_unless($user && $user->hasPermission('specialist_consultation.read')
            && app(LicenseService::class)->userHasModuleAccess($user, 'patient_records'), 403);
        $patient = $this->access->patients($user)->findOrFail($patient);
        $consultations = $this->access->consultations($user)->where('patient_id', $patient->id)->with('profile.user', 'amendments', 'carePlans')->latest()->get();
        $this->workflow->audit('historical_record_viewed', $patient);

        return response()->view('specialist.archive', compact('patient', 'consultations'))->header('Cache-Control', 'private, no-store');
    }

    public function billing(int $consultation)
    {
        $c = $this->access->consultation($consultation, 'specialist_billing.read');
        $c->load('patient.demographic', 'bill.payments.paymentMethod');

        return view('specialist.billing', ['c' => $c, 'methods' => PaymentMethod::where('is_active', true)->get()]);
    }

    public function reports(Request $r)
    {
        $u = $this->access->require('specialist_report.read');
        $d = $r->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = $d['from'] ?? today()->startOfMonth()->toDateString();
        $to = $d['to'] ?? today()->toDateString();
        abort_if(Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 366, 422, 'Choose a reporting range of up to one year.');
        $q = $this->access->consultations($u)->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
        $clinical = $u->hasPermission('specialist_consultation.read');
        $revenue = $u->hasPermission('specialist_report.revenue');
        $relations = ['patient.demographic', 'followup'];
        if ($clinical) {
            $relations = array_merge($relations, ['investigations', 'referrals', 'carePlans']);
        }
        if ($revenue) {
            $relations[] = 'bills.payments';
        }
        $rows = $q->with($relations)->get();
        $previous = $this->access->consultations($u)->whereIn('patient_id', $rows->pluck('patient_id'))->where('status', 'completed')
            ->whereNotNull('completed_at')->select('patient_id')->selectRaw('MIN(completed_at) as first_completed')->groupBy('patient_id')->pluck('first_completed', 'patient_id');
        $returning = $rows->filter(fn ($c) => isset($previous[$c->patient_id]) && Carbon::parse($previous[$c->patient_id])->lt($c->created_at))->count();

        return view('specialist.reports', compact('rows', 'from', 'to', 'revenue', 'clinical', 'returning'));
    }
}
