@extends('layouts.modern')

@section('title', 'Midwifery Dashboard')

@section('content')
    @php
        $deliveryTypeTotal = max((int) $delivery_total, 1);
        $newbornTotalSafe = max((int) $newborn_total, 1);
        $postnatalTotalSafe = max((int) $postnatal_examinations_total, 1);

        $stats = [
            ['label' => 'Antenatal Records', 'value' => $antenatal_total, 'meta' => $antenatal_today . ' today', 'variant' => 'info'],
            ['label' => 'Labour Records', 'value' => $labour_total, 'meta' => $labour_in_progress . ' in progress', 'variant' => 'warning'],
            ['label' => 'Deliveries', 'value' => $delivery_total, 'meta' => $delivery_today . ' today', 'variant' => 'success'],
            ['label' => 'Newborns Registered', 'value' => $newborn_total, 'meta' => $newborn_healthy . ' healthy', 'variant' => 'success'],
            ['label' => 'Postnatal Exams', 'value' => $postnatal_examinations_total, 'meta' => $postnatal_normal . ' normal', 'variant' => 'neutral'],
            ['label' => 'Child Follow-ups', 'value' => $child_follow_ups_total, 'meta' => $child_follow_ups_today . ' today', 'variant' => 'info'],
            ['label' => 'Pregnant Patients', 'value' => $pregnant_patients, 'meta' => 'Under care', 'variant' => 'danger'],
            ['label' => 'Newborn Exams', 'value' => $newborn_examinations_total, 'meta' => 'Completed', 'variant' => 'neutral'],
        ];
    @endphp

    <x-ui.page title="Midwifery Dashboard" subtitle="Maternity care overview across ANC, labour, delivery, newborn, postnatal, and child follow-up workflows.">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($stats as $stat)
                <x-ui.card>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-med-muted">{{ $stat['label'] }}</p>
                            <p class="mt-2 text-3xl font-semibold text-med-ink">{{ number_format($stat['value']) }}</p>
                            <p class="mt-1 text-sm text-med-muted">{{ $stat['meta'] }}</p>
                        </div>
                        <x-ui.badge :variant="$stat['variant']">Live</x-ui.badge>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.card title="Delivery Type Summary">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-med-muted">Vaginal Deliveries</p>
                                <p class="mt-2 text-3xl font-semibold text-med-success">{{ number_format($vaginal_deliveries) }}</p>
                            </div>
                            <span class="text-sm font-semibold text-med-muted">{{ round(($vaginal_deliveries / $deliveryTypeTotal) * 100) }}%</span>
                        </div>
                        <div class="mt-3 h-2 rounded-full bg-med-canvas"><div class="h-2 rounded-full bg-med-success" style="width: {{ $delivery_total > 0 ? ($vaginal_deliveries / $delivery_total * 100) : 0 }}%"></div></div>
                    </div>
                    <div>
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-med-muted">Caesarean Deliveries</p>
                                <p class="mt-2 text-3xl font-semibold text-med-warning">{{ number_format($caesarean_deliveries) }}</p>
                            </div>
                            <span class="text-sm font-semibold text-med-muted">{{ round(($caesarean_deliveries / $deliveryTypeTotal) * 100) }}%</span>
                        </div>
                        <div class="mt-3 h-2 rounded-full bg-med-canvas"><div class="h-2 rounded-full bg-med-warning" style="width: {{ $delivery_total > 0 ? ($caesarean_deliveries / $delivery_total * 100) : 0 }}%"></div></div>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Newborn Gender Distribution">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <div class="flex items-end justify-between gap-3"><div><p class="text-sm font-medium text-med-muted">Male</p><p class="mt-2 text-3xl font-semibold text-med-info">{{ number_format($newborn_males) }}</p></div><span class="text-sm font-semibold text-med-muted">{{ round(($newborn_males / $newbornTotalSafe) * 100) }}%</span></div>
                        <div class="mt-3 h-2 rounded-full bg-med-canvas"><div class="h-2 rounded-full bg-med-info" style="width: {{ $newborn_total > 0 ? ($newborn_males / $newborn_total * 100) : 0 }}%"></div></div>
                    </div>
                    <div>
                        <div class="flex items-end justify-between gap-3"><div><p class="text-sm font-medium text-med-muted">Female</p><p class="mt-2 text-3xl font-semibold text-med-danger">{{ number_format($newborn_females) }}</p></div><span class="text-sm font-semibold text-med-muted">{{ round(($newborn_females / $newbornTotalSafe) * 100) }}%</span></div>
                        <div class="mt-3 h-2 rounded-full bg-med-canvas"><div class="h-2 rounded-full bg-med-danger" style="width: {{ $newborn_total > 0 ? ($newborn_females / $newborn_total * 100) : 0 }}%"></div></div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <x-ui.card title="Health Status Summary">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-4">
                    <h3 class="text-base font-semibold text-med-ink">Newborn Status</h3>
                    @foreach([['Healthy', $newborn_healthy, 'success'], ['At Risk', $newborn_at_risk, 'warning']] as [$label, $value, $variant])
                        <div>
                            <div class="mb-2 flex items-center justify-between"><span class="text-sm text-med-muted">{{ $label }}</span><x-ui.badge :variant="$variant">{{ number_format($value) }}</x-ui.badge></div>
                            <div class="h-2 rounded-full bg-med-canvas"><div class="h-2 rounded-full {{ $variant === 'success' ? 'bg-med-success' : 'bg-med-warning' }}" style="width: {{ $newborn_total > 0 ? ($value / $newborn_total * 100) : 0 }}%"></div></div>
                        </div>
                    @endforeach
                </div>
                <div class="space-y-4">
                    <h3 class="text-base font-semibold text-med-ink">Postnatal Status</h3>
                    @foreach([['Normal', $postnatal_normal, 'success'], ['At Risk', $postnatal_at_risk, 'warning']] as [$label, $value, $variant])
                        <div>
                            <div class="mb-2 flex items-center justify-between"><span class="text-sm text-med-muted">{{ $label }}</span><x-ui.badge :variant="$variant">{{ number_format($value) }}</x-ui.badge></div>
                            <div class="h-2 rounded-full bg-med-canvas"><div class="h-2 rounded-full {{ $variant === 'success' ? 'bg-med-success' : 'bg-med-warning' }}" style="width: {{ $postnatal_examinations_total > 0 ? ($value / $postnatal_examinations_total * 100) : 0 }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.card title="Recent Antenatal Records">
                <div class="divide-y divide-med-line">
                    @forelse($recent_antenatal as $record)
                        <a href="{{ route('midwife.antenatal.show', $record) }}" class="flex items-center justify-between gap-3 py-3 transition hover:text-med-primary">
                            <span><span class="block text-sm font-semibold text-med-ink">{{ $record->patient->full_name }}</span><span class="text-sm text-med-muted">{{ $record->created_at->format('M d, Y H:i') }}</span></span>
                            <span class="text-med-muted">View</span>
                        </a>
                    @empty
                        <x-ui.empty-state title="No Antenatal Records" />
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Recent Deliveries">
                <div class="divide-y divide-med-line">
                    @forelse($recent_deliveries as $delivery)
                        <a href="{{ route('midwife.delivery.show', $delivery) }}" class="flex items-center justify-between gap-3 py-3 transition hover:text-med-primary">
                            <span><span class="block text-sm font-semibold text-med-ink">{{ $delivery->patient->full_name }}</span><span class="text-sm text-med-muted">{{ $delivery->created_at->format('M d, Y H:i') }}</span></span>
                            <span class="text-med-muted">View</span>
                        </a>
                    @empty
                        <x-ui.empty-state title="No Deliveries Recorded" />
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Recent Newborn Registrations">
                <div class="divide-y divide-med-line">
                    @forelse($recent_newborns as $newborn)
                        <a href="{{ route('midwife.newborn.show', $newborn) }}" class="flex items-center justify-between gap-3 py-3 transition hover:text-med-primary">
                            <span><span class="block text-sm font-semibold text-med-ink">{{ $newborn->newborn_registration_number }}</span><span class="text-sm text-med-muted">{{ ucfirst($newborn->sex) }} | {{ $newborn->birth_weight }}g</span></span>
                            <x-ui.badge :variant="$newborn->status === 'healthy' ? 'success' : ($newborn->status === 'at_risk' ? 'warning' : 'danger')">{{ ucfirst(str_replace('_', ' ', $newborn->status)) }}</x-ui.badge>
                        </a>
                    @empty
                        <x-ui.empty-state title="No Newborn Registrations" />
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Recent Child Follow-ups">
                <div class="divide-y divide-med-line">
                    @forelse($recent_follow_ups as $followUp)
                        <a href="{{ route('midwife.child-follow-up.show', $followUp) }}" class="flex items-center justify-between gap-3 py-3 transition hover:text-med-primary">
                            <span><span class="block text-sm font-semibold text-med-ink">{{ $followUp->newborn->newborn_registration_number }}</span><span class="text-sm text-med-muted">{{ $followUp->created_at->format('M d, Y H:i') }}</span></span>
                            <x-ui.badge :variant="$followUp->health_status === 'normal' ? 'success' : ($followUp->health_status === 'at_risk' ? 'warning' : 'danger')">{{ ucfirst(str_replace('_', ' ', $followUp->health_status)) }}</x-ui.badge>
                        </a>
                    @empty
                        <x-ui.empty-state title="No Follow-up Records" />
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
