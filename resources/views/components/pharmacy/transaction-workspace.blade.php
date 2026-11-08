<div>
    <x-ui.page title="Pharmacy Transaction" subtitle="Add medicines and services, collect one payment, and print one receipt.">
        <x-slot:actions>
            <a href="{{ route('pharmacy.transactions.index') }}" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas"><i class="bi bi-list-ul"></i>Transactions</a>
        </x-slot:actions>

        <div class="grid gap-5 xl:grid-cols-[minmax(320px,0.85fr)_minmax(0,1.35fr)]">
            <div class="order-2 space-y-5 xl:order-1">
            <x-ui.card title="Medicine Selection" subtitle="Search available, unexpired batches and add them to the cart.">
                <div class="space-y-4">
                    <x-ui.input label="Search Medicine" type="search" wire:model.live.debounce.300ms="search" placeholder="Name, generic, company, or batch" />
                    <div>
                        <x-ui.input label="Quantity" type="number" min="1" wire:model="quantity" />
                        @error('quantity')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                    </div>
                    @error('batchId')<p class="text-sm text-med-danger">{{ $message }}</p>@enderror

                    <div class="max-h-[480px] space-y-2 overflow-y-auto pr-1">
                        @forelse($batches as $batch)
                            <button type="button" class="mf-focus w-full rounded-md border border-med-line bg-white p-3 text-left transition hover:bg-med-canvas" wire:click="addBatchToCart({{ $batch->id }})">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-med-ink">{{ $batch->medicine?->name }}</p>
                                        <p class="mt-1 text-xs text-med-muted">{{ $batch->medicine?->manufacturer ?? 'N/A' }} | Batch {{ $batch->batch_number }} | Exp {{ $batch->expiry_date }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-med-ink">&#8358;{{ number_format($batch->selling_price, 2) }}</p>
                                        <p class="text-xs {{ $batch->quantity_remaining <= 10 ? 'text-med-danger' : 'text-med-primary' }}">{{ $batch->quantity_remaining }} left</p>
                                    </div>
                                </div>
                            </button>
                        @empty
                            <x-ui.empty-state title="No Medicine Found" message="No available medicine matches your search." />
                        @endforelse
                    </div>
                </div>
            </x-ui.card>
            <x-ui.card title="General Services" subtitle="Active services from the facility catalog.">
                <div class="space-y-2">
                    @forelse($catalogServices as $service)
                        <button type="button" wire:click="addCatalogServiceToCart({{ $service->id }})" class="mf-focus flex w-full items-center justify-between gap-3 rounded-md border border-med-line p-3 text-left">
                            <span>{{ $service->name }}@if($service->category)<small class="mt-1 block text-med-muted">{{ $service->category }}</small>@endif</span><strong class="shrink-0">&#8358;{{ number_format($service->price, 2) }}</strong>
                        </button>
                    @empty
                        <p class="text-med-muted">No active general services are configured.</p>
                    @endforelse
                </div>
            </x-ui.card>
            <x-ui.card title="Pharmacy Services" subtitle="Use the quantity above for each medicine or service you add.">
                @if(\App\Models\PharmacyService::canManage(auth()->user()))
                    <a href="{{ route('pharmacy.services.index') }}" class="text-med-primary underline">Manage service charges</a>
                @endif
                <div class="mt-3 space-y-2">
                    @forelse($services as $service)
                        <button type="button" wire:click="addServiceToCart({{ $service->id }})" class="mf-focus flex w-full justify-between rounded-md border border-med-line p-3 text-left">
                            <span>{{ $service->name }}</span><strong>&#8358;{{ number_format($service->price, 2) }}</strong>
                        </button>
                    @empty<p class="text-med-muted">No active services. Ask the Head of Pharmacy to set up service charges.</p>@endforelse
                </div>
            </x-ui.card>
            </div>

            <div class="order-1 space-y-5 xl:order-2">
                <x-ui.card title="Cart" subtitle="Review medicines and services before collecting payment.">
                    <x-slot:actions>
                        @if(count($cart) > 0)
                            <button type="button" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-med-danger transition hover:bg-red-50" wire:click="clearCart" wire:confirm="Clear all items from this cart?"><i class="bi bi-trash"></i>Clear</button>
                        @endif
                    </x-slot:actions>

                    <x-ui.table>
                        <thead class="bg-med-canvas/80 text-left text-xs font-semibold uppercase tracking-wide text-med-muted"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">Type / Batch</th><th class="px-4 py-3 text-right">Price</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Subtotal</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                        <tbody class="divide-y divide-med-line bg-white">
                            @forelse($cart as $index => $item)
                                <tr class="hover:bg-med-canvas/50" wire:key="cart-item-{{ $index }}"><td class="px-4 py-3 font-semibold text-med-ink">{{ $item['medicine'] }}</td><td class="px-4 py-3 text-med-muted">{{ $item['batch_number'] }}</td><td class="px-4 py-3 text-right text-med-muted">&#8358;{{ number_format($item['price'], 2) }}</td><td class="px-4 py-3 text-right text-med-muted">{{ $item['quantity'] }}</td><td class="px-4 py-3 text-right font-semibold text-med-ink">&#8358;{{ number_format($item['subtotal'], 2) }}</td><td class="px-4 py-3 text-right"><button type="button" class="mf-focus inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 text-med-danger hover:bg-red-50" wire:click="removeFromCart({{ $index }})" aria-label="Remove item"><i class="bi bi-x-lg"></i></button></td></tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8"><x-ui.empty-state title="No Items Added" message="Select a medicine or service to start this transaction." /></td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-med-canvas/60"><tr><th colspan="4" class="px-4 py-3 text-right text-med-ink">Total</th><th class="px-4 py-3 text-right text-med-ink">&#8358;{{ number_format($total, 2) }}</th><th></th></tr></tfoot>
                    </x-ui.table>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div><x-ui.input label="Patient / Customer Name" wire:model="patientName" placeholder="Required when billing a service" />
                        @error('patientName')<p class="text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                        <div><x-ui.input label="Phone (optional)" wire:model="patientPhone" maxlength="30" />
                        @error('patientPhone')<p class="text-sm text-med-danger">{{ $message }}</p>@enderror</div>
                    </div>
                    @foreach($errors->get('cart.*') as $messages) @foreach($messages as $message)<p class="text-sm text-med-danger">{{ $message }}</p>@endforeach @endforeach
                    <div class="mt-5 grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_140px] md:items-end">
                        <div>
                            <x-ui.select label="Payment Method" wire:model="paymentMethodId">
                                <option value="">Select method</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </x-ui.select>
                            @error('paymentMethodId')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror
                        </div>
                        <x-ui.input label="Payment Reference" wire:model="referenceNumber" placeholder="Optional POS/transfer reference" />
                        <button type="button" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-med-primaryDark disabled:cursor-not-allowed disabled:opacity-60" wire:click="completeTransaction" wire:loading.attr="disabled"><span wire:loading.remove wire:target="completeTransaction">Pay</span><span wire:loading wire:target="completeTransaction">Saving...</span></button>
                    </div>
                    @error('cart')<p class="mt-2 text-sm text-med-danger">{{ $message }}</p>@enderror
                </x-ui.card>

                @if($receiptTransaction)
                    <x-ui.card title="Receipt Ready" subtitle="{{ $receiptTransaction->payment?->payment_id }} | {{ $receiptTransaction->bill?->bill_number }}">
                        <x-slot:actions><button type="button" class="mf-focus inline-flex items-center justify-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas" onclick="printPharmacyThermalReceipt()"><i class="bi bi-printer"></i>Print Thermal</button></x-slot:actions>
                        <div class="grid gap-4 md:grid-cols-3"><div><p class="text-sm text-med-muted">Amount Paid</p><p class="mt-1 font-bold text-med-ink">&#8358;{{ number_format($receiptTransaction->payment?->amount ?? 0, 2) }}</p></div><div><p class="text-sm text-med-muted">Payment Method</p><p class="mt-1 font-bold text-med-ink">{{ $receiptTransaction->payment?->paymentMethod?->name ?? 'N/A' }}</p></div><div><p class="text-sm text-med-muted">Collected By</p><p class="mt-1 font-bold text-med-ink">{{ $receiptTransaction->createdBy?->name ?? 'System' }}</p></div></div>
                    </x-ui.card>

                    <div id="pharmacy-thermal-receipt" class="hidden">@include('pharmacy.partials.combined-receipt', ['transaction' => $receiptTransaction, 'payment' => $receiptTransaction->payment])</div>
                @endif
            </div>
        </div>

        <style>.thermal-receipt{font-family:monospace;color:#000;width:72mm;max-width:72mm;font-size:13.5px;line-height:1.25}.thermal-receipt p{margin:0 0 3px}.thermal-receipt table{width:100%;border-collapse:collapse}.thermal-receipt td{padding:2px 0;vertical-align:top}.thermal-receipt .divider{border-top:1px dashed #000;margin:6px 0}.thermal-receipt .text-right{text-align:right}</style>
        @push('scripts')
        @include('pharmacy.partials.thermal-printer')
            <script>
                function printPharmacyThermalReceipt(){var template=document.getElementById('pharmacy-thermal-receipt');if(!template){return alert('Pharmacy receipt template not found.')}printPharmacyReceipt(template.innerHTML)}
                document.addEventListener('livewire:init',function(){Livewire.on('print-pharmacy-thermal',function(){setTimeout(printPharmacyThermalReceipt,400)})});
            </script>
        @endpush
    </x-ui.page>
</div>
