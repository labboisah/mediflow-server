<x-ui.page :title="$pageTitle ?? 'Workspace'" :subtitle="$pageSubtitle ?? config('app.name')">
    <x-ui.table>
        <thead class="bg-med-canvas">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Model</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-med-ink">Table</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-med-ink">Pending</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-med-ink">Failed</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-med-ink">Queued</th>
                <th class="px-4 py-3 text-right text-sm font-semibold text-med-ink">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-med-line">
            @forelse($models as $model)
                <tr class="hover:bg-med-canvas">
                    <td class="px-4 py-4 font-semibold text-med-ink">{{ $model['name'] }}</td>
                    <td class="px-4 py-4 text-sm text-med-muted">{{ $model['table'] }}</td>
                    <td class="px-4 py-4 text-center">
                        <x-ui.badge :variant="$model['pending'] ? 'warning' : 'success'">{{ $model['pending'] }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <x-ui.badge :variant="$model['failed'] ? 'danger' : 'success'">{{ $model['failed'] }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <x-ui.badge variant="info">{{ $model['queued'] }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex justify-end">
                            @if($model['pending'] || $model['failed'])
                                <x-ui.button type="button" wire:click="sync(@js($model['class']))" wire:loading.attr="disabled" wire:target="sync">
                                    <span wire:loading.remove wire:target="sync">Sync</span>
                                    <span wire:loading wire:target="sync">Queuing...</span>
                                </x-ui.button>
                            @else
                                <x-ui.badge variant="success">Synchronized</x-ui.badge>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8">
                        <x-ui.empty-state title="No syncable models found" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.page>

