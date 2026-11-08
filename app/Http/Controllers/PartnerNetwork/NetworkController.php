<?php

namespace App\Http\Controllers\PartnerNetwork;

use App\Http\Controllers\Controller;
use App\Models\CollaborationPartner;
use App\Models\PartnerCaseEvent;
use App\Models\PartnerCollaboration;
use App\Models\PartnerDisclosure;
use App\Models\PartnerDocument;
use App\Models\PartnerMembership;
use App\Models\PartnerOnboardingSubmission;
use App\Models\PartnerService;
use App\Models\Partnership;
use App\Models\PartnershipAgreement;
use App\Models\PartnerSubmission;
use App\Models\PartnerType;
use App\Services\PartnerNetwork\Access;
use App\Services\PartnerNetwork\Collaboration;
use App\Services\PartnerNetwork\Workflow;
use App\Services\SpecialistAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NetworkController extends Controller
{
    public function __construct(public Access $access, public Workflow $workflow, public Collaboration $collaboration) {}

    public function index(Request $r)
    {
        $this->access->staff('partner.read');
        $search = $r->validate(['search' => ['nullable', 'string', 'max:255'], 'type' => ['nullable', 'integer'], 'status' => ['nullable', 'string', 'max:40'], 'service' => ['nullable', 'string', 'max:255'], 'location' => ['nullable', 'string', 'max:255']]);
        $q = Partnership::query()->join('collaboration_partners as p', 'p.id', '=', 'partnerships.partner_id')->select('partnerships.*', 'p.name', 'p.contact');
        $q->when($search['search'] ?? null, fn ($q, $v) => $q->where('p.name', 'like', '%'.$v.'%'))->when($search['type'] ?? null, fn ($q, $v) => $q->where('partner_type_id', $v))->when($search['status'] ?? null, fn ($q, $v) => $q->where('status', $v));
        foreach (['service' => 'name', 'location' => 'location'] as $filter => $column) {
            if (! empty($search[$filter])) {
                $q->whereIn('partner_id', PartnerService::where($column, 'like', '%'.$search[$filter].'%')->select('partner_id'));
            }
        }

        return view('network.index', ['rows' => $q->latest('partnerships.id')->paginate(20), 'types' => PartnerType::all(), 'existing' => CollaborationPartner::where('is_local', false)->orderBy('name')->get(), 'counts' => Partnership::select('status')->selectRaw('count(*) as total')->groupBy('status')->pluck('total', 'status')]);
    }

    public function store(Request $r)
    {
        $p = $this->workflow->register($r->all());

        return redirect()->route('network.show', $p);
    }

    public function show(int $id)
    {
        $this->access->staff('partner.read');
        $p = Partnership::findOrFail($id);

        return view('network.partnership', $this->data($p));
    }

    public function data(Partnership $p): array
    {
        return ['p' => $p, 'partner' => CollaborationPartner::findOrFail($p->partner_id), 'type' => PartnerType::findOrFail($p->partner_type_id), 'submission' => PartnerOnboardingSubmission::where('partnership_id', $p->id)->latest('version')->first(), 'agreement' => PartnershipAgreement::where('partnership_id', $p->id)->latest('version')->first(), 'services' => PartnerService::where('partner_id', $p->partner_id)->get(), 'members' => PartnerMembership::join('users', 'users.id', '=', 'partner_memberships.user_id')->where('partner_id', $p->partner_id)->select('partner_memberships.*', 'users.name', 'users.email')->get(), 'documents' => PartnerDocument::where('partnership_id', $p->id)->whereNull('collaboration_id')->get()];
    }

    public function action(Request $r, int $id, string $action)
    {
        if ($action === 'invite') {
            return back()->with('invitation_link', route('partner.invitation', ['token' => $this->workflow->invite($id, $r->all())]));
        }
        match ($action) {
            'transition' => $this->workflow->transition($id, $r->all()),'agreement' => $this->workflow->agreement($id, $r->all()),'service' => $this->service($r, $id, false),'membership' => $this->membership($r, $id), 'edit' => $this->edit($r, $id),default => abort(404)
        };

        return back()->with('success', 'Saved.');
    }

    public function service(Request $r, int $id, bool $portal)
    {
        $p = Partnership::findOrFail($id);
        $u = $portal ? $this->access->member($p, true) : $this->access->staff('partner_service.manage');
        $d = $r->validate(['name' => ['required', 'string', 'max:255'], 'specialty' => ['nullable', 'string', 'max:255'], 'location' => ['required', 'string', 'max:255'], 'availability' => ['nullable', 'string', 'max:255']]);
        $s = PartnerService::create($d + ['partner_id' => $p->partner_id]);
        $this->workflow->audit('service_proposed', $s, $u->id);
    }

    public function membership(Request $r, int $id)
    {
        $u = $this->access->staff('partnership.manage');
        $d = $r->validate(['membership_id' => ['required', 'integer'], 'is_active' => ['required', 'boolean']]);
        $p = Partnership::findOrFail($id);
        $m = PartnerMembership::where('partner_id', $p->partner_id)->findOrFail($d['membership_id']);
        $m->update(['is_active' => $d['is_active']]);
        $this->workflow->audit('membership_changed', $m, $u->id);
    }

    public function types(Request $r)
    {
        $u = $this->access->staff('partner_requirement.manage');
        $d = $r->validate(['code' => ['required', 'regex:/^[a-z][a-z0-9_]{2,40}$/'], 'name' => ['required', 'string', 'max:255'], 'requirements' => ['required', 'string', 'max:5000']]);
        $requirements = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r?\n/', $d['requirements'])))));
        abort_unless(count($requirements) > 0 && count($requirements) <= 30, 422);
        DB::transaction(function () use ($d, $requirements, $u) {
            $t = PartnerType::where('code', $d['code'])->lockForUpdate()->first() ?? new PartnerType(['code' => $d['code']]);
            $t->fill(['name' => $d['name'], 'requirements' => $requirements, 'version' => ($t->version ?? 0) + 1]);
            $t->save();
            $this->workflow->audit('requirements_updated', $t, $u->id);
        });

        return back()->with('success', 'Requirement template saved. Existing submissions retain their version.');
    }

    public function createCase(int $consultation)
    {
        $this->access->staff('partner_collaboration.create');
        $c = app(SpecialistAccess::class)->consultation($consultation, 'specialist_consultation.read');
        $options = [];
        foreach (Partnership::where('status', 'active')->get() as $p) {
            try {
                $a = $this->access->agreement($p);
            } catch (HttpException $e) {
                continue;
            }$partner = CollaborationPartner::find($p->partner_id);
            foreach (PartnerService::where('partner_id', $p->partner_id)->where('is_active', true)->whereIn('id', $a->service_ids)->get() as $s) {
                $options[] = ['p' => $p, 'partner' => $partner, 'service' => $s, 'capabilities' => $a->capabilities];
            }
        }

        return view('network.dispatch', compact('c', 'options'));
    }

    public function dispatch(Request $r, int $consultation)
    {
        $c = $this->collaboration->dispatch($consultation, $r->all());

        return redirect()->route('network.case', $c);
    }

    public function cases()
    {
        $u = $this->access->staff('partner_collaboration.read');
        $ids = app(SpecialistAccess::class)->consultations($u)->select('id');
        $rows = PartnerCollaboration::whereIn('consultation_id', $ids)->latest()->paginate(30);

        return view('network.cases', compact('rows'));
    }

    public function case(int $id)
    {
        $c = PartnerCollaboration::findOrFail($id);
        $this->access->clinical($c);
        $this->workflow->audit('case_viewed', $c, auth()->id());

        return view('network.case', $this->caseData($c) + ['portal' => false]);
    }

    public function caseData(PartnerCollaboration $c): array
    {
        return ['c' => $c, 'disclosure' => PartnerDisclosure::where('collaboration_id', $c->id)->latest('id')->firstOrFail(), 'submissions' => PartnerSubmission::where('collaboration_id', $c->id)->latest('version')->get(), 'events' => PartnerCaseEvent::where('collaboration_id', $c->id)->latest()->get(), 'documents' => PartnerDocument::where('collaboration_id', $c->id)->get()];
    }

    public function caseAction(Request $r, int $id, string $action)
    {
        match ($action) {
            'review' => $this->collaboration->review($id, $r->all()),'revoke' => $this->collaboration->revoke($id), 'disclose' => $this->collaboration->disclose($id, $r->all()), 'cancel' => $this->collaboration->cancel($id, $r->all()),default => abort(404)
        };

        return back()->with('success', 'Saved.');
    }

    public function reports(Request $r)
    {
        $this->access->staff('partner_report.read');
        $d = $r->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $from = Carbon::parse($d['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($d['to'] ?? now())->endOfDay();
        abort_unless($to->gte($from) && $from->diffInDays($to) <= 366, 422, 'Select a range up to one year.');
        $query = DB::table('partner_collaborations')->whereBetween('partner_collaborations.created_at', [$from, $to]);
        $rows = (clone $query)->join('partnerships', 'partnerships.id', '=', 'partner_collaborations.partnership_id')->join('collaboration_partners', 'collaboration_partners.id', '=', 'partnerships.partner_id')->select('collaboration_partners.name', 'partner_collaborations.kind', 'partner_collaborations.status')->selectRaw('count(*) as total')->groupBy('collaboration_partners.name', 'partner_collaborations.kind', 'partner_collaborations.status')->get();
        $completionMinutes = [];
        $responseMinutes = [];
        foreach ((clone $query)->select('created_at', 'accepted_at', 'completed_at')->get() as $case) {
            if ($case->accepted_at) {
                $responseMinutes[] = Carbon::parse($case->created_at)->diffInMinutes(Carbon::parse($case->accepted_at));
            }
            if ($case->accepted_at && $case->completed_at) {
                $completionMinutes[] = Carbon::parse($case->accepted_at)->diffInMinutes(Carbon::parse($case->completed_at));
            }
        }

        return view('network.reports', compact('rows', 'from', 'to', 'responseMinutes', 'completionMinutes'));
    }

    public function edit(Request $r, int $id): void
    {
        $u = $this->access->staff('partner.edit');
        $d = $r->validate(['name' => ['required', 'string', 'max:255'], 'contact' => ['required', 'string', 'max:255'], 'reason' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($id, $d, $u) {
            $p = Partnership::lockForUpdate()->findOrFail($id);
            abort_if($p->status === 'terminated', 422, 'Terminated relationships are historical.');
            $partner = CollaborationPartner::findOrFail($p->partner_id);
            $partner->update(['name' => $d['name'], 'contact' => $d['contact']]);
            Partnership::where('partner_id', $partner->id)->whereIn('status', ['active', 'approved', 'onboarding'])->update(['status' => 'under_review', 'version' => DB::raw('version + 1')]);
            $this->workflow->audit('provider_identity_changed', $partner, $u->id, ['reason' => $d['reason']]);
        });
    }
}
