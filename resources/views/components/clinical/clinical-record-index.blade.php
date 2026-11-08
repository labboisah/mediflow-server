<div>
    <x-ui.page :title="$config['title']" subtitle="Recorded clinicals across patients. Open a row to edit from that patient workspace.">
        <x-ui.card title="Records" subtitle="Search by hospital number or patient name.">
            <div class="mb-5 grid gap-4 md:grid-cols-[minmax(0,1fr)_160px]">
                <x-ui.input label="Search patient" type="search" wire:model.live.debounce.400ms="search" placeholder="Hospital number or patient name" />
                <x-ui.select label="Rows" wire:model.live="perPage">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </x-ui.select>
            </div>

            @include("components.clinical.record-indexes.{$type}", [
                'records' => $records,
                'config' => $config,
            ])

            <div class="mt-5">
                {{ $records->links() }}
            </div>
        </x-ui.card>
    </x-ui.page>
</div>
