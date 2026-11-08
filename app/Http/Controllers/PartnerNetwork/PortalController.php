<?php

namespace App\Http\Controllers\PartnerNetwork;

use App\Http\Controllers\Controller;
use App\Models\PartnerCollaboration;
use App\Models\PartnerMembership;
use App\Models\Partnership;
use App\Services\PartnerNetwork\Access;
use App\Services\PartnerNetwork\Collaboration;
use App\Services\PartnerNetwork\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PortalController extends Controller
{
    public function __construct(public Access $access, public Workflow $workflow, public Collaboration $collaboration) {}

    public function loginForm()
    {
        return view('network.login');
    }

    public function login(Request $r)
    {
        $d = $r->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::guard('partner')->attempt($d)) {
            return back()->withErrors(['email' => 'Credentials could not be verified.']);
        }$r->session()->regenerate();

        return redirect()->route('partner.index');
    }

    public function logout(Request $r)
    {
        Auth::guard('partner')->logout();
        $r->session()->regenerateToken();

        return redirect()->route('partner.login');
    }

    public function invitation(string $token)
    {
        return view('network.invitation', compact('token'));
    }

    public function redeem(Request $r, string $token)
    {
        $u = $this->workflow->redeem($token, $r->all());
        Auth::guard('partner')->login($u);
        $r->session()->regenerate();

        return redirect()->route('partner.index');
    }

    public function index()
    {
        $this->access->enabled();
        $u = Auth::guard('partner')->user();
        abort_unless($u && $u->email_verified_at, 403);
        $partners = PartnerMembership::where('user_id', $u->id)->where('is_active', true)->pluck('partner_id');
        $partnerships = Partnership::whereIn('partner_id', $partners)->get();
        $cases = collect();
        foreach (PartnerCollaboration::whereIn('partnership_id', $partnerships->pluck('id'))->latest()->limit(200)->get() as $c) {
            try {
                $this->access->portalCase($c);
                $cases->push($c);
            } catch (HttpException $e) {
            }
        }

        return view('network.portal', compact('partnerships', 'cases'));
    }

    public function show(int $id)
    {
        $p = Partnership::findOrFail($id);
        $this->access->member($p, true);

        return view('network.onboarding', app(NetworkController::class)->data($p));
    }

    public function action(Request $r, int $id, string $action)
    {
        if ($action === 'invite') {
            return back()->with('invitation_link', route('partner.invitation', ['token' => $this->workflow->invite($id, $r->all(), true)]));
        }
        match ($action) {
            'submit' => $this->workflow->submit($id, $r->all()),'acknowledge' => $this->workflow->acknowledge($id),'service' => app(NetworkController::class)->service($r, $id, true),default => abort(404)
        };

        return back()->with('success', 'Saved.');
    }

    public function case(int $id)
    {
        $c = PartnerCollaboration::findOrFail($id);
        $u = $this->access->portalCase($c);
        $this->workflow->audit('portal_case_viewed', $c, $u->id);

        return view('network.case', app(NetworkController::class)->caseData($c) + ['portal' => true]);
    }

    public function caseAction(Request $r, int $id, string $action)
    {
        match ($action) {
            'respond' => $this->collaboration->respond($id, $r->all()),'submit' => $this->collaboration->submit($id, $r->all()), 'assign' => $this->collaboration->assign($id, $r->all()),default => abort(404)
        };

        return back()->with('success', 'Saved.');
    }
}
