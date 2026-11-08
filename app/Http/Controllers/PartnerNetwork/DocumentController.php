<?php

namespace App\Http\Controllers\PartnerNetwork;

use App\Http\Controllers\Controller;
use App\Models\PartnerCollaboration;
use App\Models\PartnerDocument;
use App\Models\Partnership;
use App\Services\PartnerNetwork\Access;
use App\Services\PartnerNetwork\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(public Access $access, public Workflow $workflow) {}

    private function authorizeOwner(Request $r, Partnership $p, ?PartnerCollaboration $c)
    {
        if (str_starts_with($r->route()->getName(), 'partner.')) {
            return $c ? $this->access->portalCase($c, true, 'exchange_documents') : $this->access->member($p, true);
        }
        if ($c) {
            $this->access->clinical($c);

            return auth()->user();
        }

        return $this->access->staff('partner.verify');
    }

    public function upload(Request $r, int $id)
    {
        $p = Partnership::findOrFail($id);
        $d = $r->validate(['collaboration_id' => ['nullable', 'integer'], 'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        $c = isset($d['collaboration_id']) ? PartnerCollaboration::where('partnership_id', $id)->findOrFail($d['collaboration_id']) : null;
        $u = $this->authorizeOwner($r, $p, $c);
        if ($c && ! str_starts_with($r->route()->getName(), 'partner.')) {
            $r->validate(['authorize_sharing' => ['accepted']]);
        }
        $file = $d['document'];
        $path = $file->store('partner-documents', 'local');
        abort_unless($path, 503, 'Private storage unavailable.');
        try {
            DB::transaction(function () use ($r, $p, $c, $u, $file, $path) {
                $locked = Partnership::lockForUpdate()->findOrFail($p->id);
                $this->authorizeOwner($r, $locked, $c?->fresh());
                $doc = PartnerDocument::create(['partnership_id' => $p->id, 'collaboration_id' => $c?->id, 'name' => basename($file->getClientOriginalName()), 'path' => $path, 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()), 'uploaded_by' => $u->id]);
                $this->workflow->audit('document_uploaded', $doc, $u->id);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return back()->with('success', 'Uploaded privately; document awaits release under the configured review policy.');
    }

    public function download(Request $r, int $id)
    {
        $d = PartnerDocument::findOrFail($id);
        $p = Partnership::findOrFail($d->partnership_id);
        $c = $d->collaboration_id ? PartnerCollaboration::findOrFail($d->collaboration_id) : null;
        $u = $this->authorizeOwner($r, $p, $c);
        if (str_starts_with($r->route()->getName(), 'partner.')) {
            abort_unless($d->status === 'released', 403, 'Document awaits review.');
        }$this->workflow->audit('document_downloaded', $d, $u->id);

        return Storage::disk('local')->download($d->path, $d->name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function release(Request $r, int $id)
    {
        $u = $this->access->staff('partner.verify');
        abort_unless(config('partner_network.allow_manual_document_release'), 403, 'Manual document release is disabled. Configure the approved document review policy first.');
        $d = $r->validate(['reason' => ['required', 'string', 'max:5000']]);
        $doc = PartnerDocument::findOrFail($id);
        if ($doc->collaboration_id) {
            $this->access->clinical(PartnerCollaboration::findOrFail($doc->collaboration_id));
        }$doc->update(['status' => 'released', 'released_by' => $u->id, 'release_reason' => $d['reason']]);
        $this->workflow->audit('document_released', $doc, $u->id);

        return back()->with('success', 'Document released under manual review policy.');
    }
}
