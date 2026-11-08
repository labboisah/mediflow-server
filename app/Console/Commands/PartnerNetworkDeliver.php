<?php

namespace App\Console\Commands;

use App\Models\PartnerCollaboration;
use App\Models\PartnerMembership;
use App\Models\PartnerOutbox;
use App\Models\Partnership;
use App\Models\User;
use App\Services\PartnerNetwork\Access;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PartnerNetworkDeliver extends Command
{
    protected $signature = 'partners:deliver {--limit=100} {--retry-failed}';

    protected $description = 'Deliver minimal portal availability notices from the Care Network outbox';

    public function handle(): int
    {
        app(Access::class)->enabled();
        if ($this->option('retry-failed')) {
            PartnerOutbox::where('status', 'failed')->update(['status' => 'retry', 'attempts' => 0, 'next_attempt_at' => now()]);
        }
        foreach (PartnerOutbox::whereIn('status', ['pending', 'retry'])->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))->limit(min(500, max(1, (int) $this->option('limit'))))->pluck('id') as $id) {
            DB::transaction(function () use ($id) {
                $e = PartnerOutbox::lockForUpdate()->findOrFail($id);
                if (! in_array($e->status, ['pending', 'retry'])) {
                    return;
                }
                $e->attempts++;
                try {
                    $c = PartnerCollaboration::findOrFail($e->collaboration_id);
                    $p = Partnership::findOrFail($c->partnership_id);
                    app(Access::class)->agreement($p);
                    if (config('partner_network.notifications_enabled')) {
                        foreach (User::whereIn('id', PartnerMembership::where('partner_id', $p->partner_id)->where('is_active', true)->whereIn('role', ['intake', 'administrator_intake'])->select('user_id'))->get() as $u) {
                            Mail::raw('An authorized request is available in your Care Network portal. Sign in: '.route('partner.login'), fn ($m) => $m->to($u->email)->subject('Care Network: request available'));
                        }
                    }
                    $e->status = config('partner_network.notifications_enabled') ? 'notified' : 'portal_available';
                    $e->delivered_at = now();
                    $e->error = null;
                } catch (\Throwable $failure) {
                    $e->status = $e->attempts >= 5 ? 'failed' : 'retry';
                    $e->next_attempt_at = now()->addMinutes(min(60, 2 ** $e->attempts));
                    $e->error = 'Delivery unavailable; check relationship validity and configured mail transport.';
                }$e->save();
            });
        }
        $this->info('Outbox processed. Portal availability is independent of optional email delivery.');

        return self::SUCCESS;
    }
}
