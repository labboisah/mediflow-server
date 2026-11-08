{{-- Admin-Specific Report Content --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-shield-check"></i> System Overview Report</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Total User Activities</h6>
                    <h3 class="mb-0 text-dark">{{ $reportData['total_activities'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">Active Users</h6>
                    <h3 class="mb-0 text-dark">{{ $reportData['active_users'] ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted mb-2">System Transactions</h6>
                    <h3 class="mb-0 text-dark">{{ $reportData['system_transactions'] ?? 0 }}</h3>
                </div>
            </div>
        </div>

        {{-- Activity Summary --}}
        @if(!empty($reportData['activity_summary']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-graph-up"></i> Activity Summary by Department</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Total Activities</th>
                                <th>Active Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['activity_summary'] ?? [] as $summary)
                                <tr>
                                    <td>{{ $summary['department'] ?? 'N/A' }}</td>
                                    <td>{{ $summary['total_activities'] ?? 0 }}</td>
                                    <td>{{ $summary['active_users'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No activity data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- System Health --}}
        @if(!empty($reportData['system_health']))
            <div class="mb-4">
                <h6 class="mb-3"><i class="bi bi-cpu"></i> System Health Status</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Database Status:</strong> <span class="badge bg-success">{{ $reportData['system_health']['database'] ?? 'Operational' }}</span></p>
                        <p><strong>API Status:</strong> <span class="badge bg-success">{{ $reportData['system_health']['api'] ?? 'Operational' }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Server Load:</strong> <span class="badge bg-info">{{ $reportData['system_health']['server_load'] ?? 'Normal' }}</span></p>
                        <p><strong>Cache Status:</strong> <span class="badge bg-success">{{ $reportData['system_health']['cache'] ?? 'Active' }}</span></p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>