@extends('layouts.modern')

@section('title', 'Data Backup')
@section('page-title', 'Data Backup')
@section('page-subtitle', 'Create database backups and review current database size.')

@section('content')
    <x-ui.page title="Data Backup" subtitle="Create database backups and review current database size.">
        <div class="grid gap-6 xl:grid-cols-12">
            <x-ui.card class="xl:col-span-4" title="Database Size">
                <p class="text-3xl font-bold text-med-ink">{{ $databaseSize['formatted'] }}</p>
            </x-ui.card>

            <x-ui.card class="xl:col-span-8" title="Create Database Backup">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <span class="mf-icon-box">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <div>
                            <p class="font-semibold text-med-ink">Backup current database</p>
                            <p class="mt-1 text-sm leading-6 text-med-muted">Backups are saved to a connected USB drive first, then to the current user's Downloads folder if no USB drive is available.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.backup.store') }}">
                        @csrf
                        <x-ui.button type="submit">
                            <i class="bi bi-database-down"></i>
                            Backup Database
                        </x-ui.button>
                    </form>
                </div>
            </x-ui.card>
        </div>
    </x-ui.page>
@endsection
