<div>
    <x-ui.page title="Pharmacy Services" subtitle="Set charges for injection, wound dressing, first aid, consultation, and other pharmacy services.">
        <x-ui.card title="{{ $editingId ? 'Edit Service' : 'Add Service' }}">
            @if(session('serviceSaved'))<p class="mb-4 text-med-primary">{{ session('serviceSaved') }}</p>@endif
            <form wire:submit="save" class="space-y-4">
                <x-ui.input label="Service Name" wire:model="name" maxlength="255" required />
                @error('name')<p class="text-med-danger">{{ $message }}</p>@enderror
                <x-ui.input label="Charge (NGN)" type="number" min="0.01" step="0.01" wire:model="price" required />
                @error('price')<p class="text-med-danger">{{ $message }}</p>@enderror
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="isActive">Available for billing</label>
                <x-ui.button type="submit" wire:loading.attr="disabled">Save Service</x-ui.button>
                @if($editingId)<x-ui.button type="button" variant="secondary" wire:click="cancel">Cancel</x-ui.button>@endif
            </form>
        </x-ui.card>
        <x-ui.card title="Service Charges" subtitle="Deactivate a service to stop new billing. Existing receipts retain their original charges.">
            <x-ui.table>
                <thead><tr><th class="px-4 py-3 text-left">Service</th><th>Charge</th><th>Status</th><th></th></tr></thead>
                <tbody>@forelse($services as $service)
                    <tr wire:key="service-{{ $service->id }}"><td class="px-4 py-3">{{ $service->name }}</td><td class="text-center">&#8358;{{ number_format($service->price, 2) }}</td><td class="text-center">{{ $service->is_active ? 'Active' : 'Inactive' }}</td><td><x-ui.button type="button" variant="secondary" wire:click="edit({{ $service->id }})">Edit</x-ui.button></td></tr>
                @empty<tr><td colspan="4" class="px-4 py-6">Add the services offered by your pharmacy and set their charges.</td></tr>@endforelse</tbody>
            </x-ui.table>
        </x-ui.card>
    </x-ui.page>
</div>
