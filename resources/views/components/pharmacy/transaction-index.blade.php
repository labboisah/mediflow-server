<div>
    <x-ui.page title="Pharmacy Transactions" subtitle="Search, filter, and review pharmacy transactions.">
        <x-slot:actions>
            <a href="{{ route('pharmacy.transactions.report') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas"><i class="bi bi-graph-up"></i>Report</a>
            <a href="{{ route('pharmacy.transactions.create') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark"><i class="bi bi-plus-circle"></i>Add Transaction</a>
        </x-slot:actions>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.card><p class="text-sm font-medium text-med-muted">Transactions</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['count']) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Transaction Amount</p><p class="mt-2 text-2xl font-bold text-med-ink">&#8358;{{ number_format($summary['amount'], 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Payment Collected</p><p class="mt-2 text-2xl font-bold text-med-ink">&#8358;{{ number_format($summary['payments'], 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm font-medium text-med-muted">Items Dispensed</p><p class="mt-2 text-2xl font-bold text-med-ink">{{ number_format($summary['items']) }}</p></x-ui.card>
        </div>

        <x-ui.card title="Filters">
            <div class="grid gap-4 xl:grid-cols-12 xl:items-end">
                <div class="xl:col-span-4"><x-ui.input label="Search" type="search" wire:model.live.debounce.300ms="search" placeholder="Medicine, service, patient, bill, receipt" /></div>
                <div class="xl:col-span-2"><x-ui.input label="From" type="date" wire:model.live="from" /></div>
                <div class="xl:col-span-2"><x-ui.input label="To" type="date" wire:model.live="to" /></div>
                <div class="xl:col-span-2">
                    <x-ui.select label="Payment Method" wire:model.live="paymentMethod">
                        <option value="">All methods</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="xl:col-span-2">
                    <x-ui.select label="Created By" wire:model.live="createdBy">
                        <option value="">All staff</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="xl:col-span-12"><x-ui.button type="button" variant="secondary" wire:click="resetFilters"><i class="bi bi-arrow-counterclockwise"></i>Reset Filters</x-ui.button></div>
            </div>
        </x-ui.card>

        <x-ui.card title="Transactions">
            <x-ui.table>
                <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted">
                    <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Medicines / Services</th><th class="px-4 py-3">Bill</th><th class="px-4 py-3">Payment</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Created By</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-right">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-med-line bg-white">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-med-canvas/50" wire:key="transaction-{{ $transaction->id }}">
                            <td class="px-4 py-3 text-med-muted">{{ $transaction->created_at?->format('M d, Y h:i A') }}</td>
                            <td class="px-4 py-3 text-med-muted">@foreach($transaction->stockTransactionItems as $item)<div>{{ $item->medicineBatch?->medicine?->name ?? 'N/A' }} <span class="text-med-muted/80">x {{ $item->quantity }}</span></div>@endforeach @foreach($transaction->serviceItems as $item)<div>{{ $item->name }} x {{ $item->quantity }} (Service)</div>@endforeach @if($transaction->patient_name)<p class="text-xs">Patient: {{ $transaction->patient_name }}</p>@endif</td>
                            <td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $transaction->bill?->bill_number ?? 'N/A' }}</p><p class="text-xs text-med-muted">{{ ucfirst($transaction->bill?->status ?? 'unknown') }}</p></td>
                            <td class="px-4 py-3"><p class="font-semibold text-med-ink">{{ $transaction->payment?->payment_id ?? 'N/A' }}</p>@if($transaction->payment?->reference_number)<p class="text-xs text-med-muted">Ref: {{ $transaction->payment->reference_number }}</p>@endif</td>
                            <td class="px-4 py-3 text-med-muted">{{ $transaction->payment?->paymentMethod?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-med-muted">{{ $transaction->createdBy?->name ?? 'System' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-med-ink">&#8358;{{ number_format($transaction->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">@if($transaction->payment)<a href="{{ route('pharmacy.receipts.show', $transaction->payment) }}" class="mf-focus rounded-md border border-med-line px-3 py-1.5 text-sm font-semibold text-med-primary transition hover:bg-med-canvas">Receipt</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8"><x-ui.empty-state title="No Transactions" message="No transaction matched the current filters." /></td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
            @if($transactions->hasPages())<div class="mt-5">{{ $transactions->links() }}</div>@endif
        </x-ui.card>
    </x-ui.page>
</div>
