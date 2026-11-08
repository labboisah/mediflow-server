@extends('layouts.modern')

@section('title', 'System Update')
@section('page-title', 'System Update')
@section('page-subtitle', 'Compare installed and remote versions, then run available updates.')

@section('content')
    <x-ui.page title="System Update" subtitle="Compare installed and remote versions, then run available updates.">
        <div class="grid gap-6 xl:grid-cols-12">
            @if(! empty($connectionError))
                <div class="xl:col-span-12 rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                    <i class="bi bi-exclamation-triangle"></i>
                    {{ $connectionError }}
                </div>
            @endif

            <x-ui.card class="xl:col-span-6" title="Current System">
                <code class="block rounded-md bg-slate-950 p-4 text-sm text-sky-300 break-all">{{ $local ?? 'Unavailable' }}</code>
            </x-ui.card>

            <x-ui.card class="xl:col-span-6" title="Remote Repository">
                <code class="block rounded-md bg-slate-950 p-4 text-sm text-green-300 break-all">{{ $remote ?? 'Unavailable' }}</code>
            </x-ui.card>

            <x-ui.card class="xl:col-span-12" title="Update Status">
                @if($hasUpdate)
                    <div class="space-y-5">
                        <div class="rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                            <i class="bi bi-exclamation-triangle"></i>
                            New update available.
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm font-semibold text-med-ink">
                                <span>Progress</span>
                                <span id="progressText">0%</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-med-canvas">
                                <div id="updateProgressBar" class="h-full rounded-full bg-med-primary transition-all" style="width: 0%"></div>
                            </div>
                        </div>

                        <div id="updateLogs" class="h-72 overflow-auto rounded-md bg-slate-950 p-4 font-mono text-sm text-slate-100">
                            <div class="text-slate-400">Waiting for update process...</div>
                        </div>

                        <x-ui.button type="button" id="startUpdateBtn">
                            <i class="bi bi-download"></i>
                            Install Update
                        </x-ui.button>
                    </div>
                @else
                    <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        <i class="bi bi-check-circle"></i>
                        System is up to date.
                    </div>
                @endif
            </x-ui.card>
        </div>
    </x-ui.page>

    @if($hasUpdate)
        <script>
            document.getElementById('startUpdateBtn').addEventListener('click', async function () {
                const button = this;
                const progressBar = document.getElementById('updateProgressBar');
                const progressText = document.getElementById('progressText');
                const logs = document.getElementById('updateLogs');

                button.disabled = true;
                button.innerHTML = '<span>Updating System...</span>';
                logs.innerHTML = '';

                try {
                    const response = await fetch("{{ route('admin.system.update.run') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        }
                    });

                    const data = await response.json();

                    if (data.results) {
                        data.results.forEach((result) => {
                            progressBar.style.width = result.progress + '%';
                            progressText.innerHTML = result.progress + '%';
                            logs.innerHTML += '<div class="mb-4"><div class="mb-2 font-semibold text-sky-300">&gt; ' + result.title + '</div><pre class="mb-0 whitespace-pre-wrap text-slate-100">' + result.output.join("\n") + '</pre></div>';
                            logs.scrollTop = logs.scrollHeight;
                        });
                    }

                    if (data.success) {
                        logs.innerHTML += '<div class="mt-3 rounded-md bg-green-900/40 p-3 text-green-200">System updated successfully</div>';
                        button.innerHTML = '<span>Update Completed</span>';
                    } else {
                        logs.innerHTML += '<div class="mt-3 rounded-md bg-red-900/40 p-3 text-red-200">Update failed</div>';
                        button.disabled = false;
                        button.innerHTML = '<span>Retry Update</span>';
                    }
                } catch (error) {
                    logs.innerHTML += '<div class="mt-3 rounded-md bg-red-900/40 p-3 text-red-200">' + error.message + '</div>';
                    button.disabled = false;
                    button.innerHTML = '<span>Retry Update</span>';
                }
            });
        </script>
    @endif
@endsection
