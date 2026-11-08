@php 
$requests = auth()->user()->department->requestStats();
$revenue = auth()->user()->department->revenue();
@endphp
<div class="container-fluid">
    {{-- investigation Overview Cards --}}
    <div class="card-body shadow p-4 mb-4">
        <h5 class="card-title mb-4">Investigation Request Overview</h5>
        <div class="row mb-4">
            <div class="col-md-3 mb-2">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Total Request Today</p>
                                <h5 class="mb-0">{{$requests['today']}}</h5>
                            </div>
                            <i class="bi bi-file-earmark-text text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Paid Request</p>
                                <h5 class="mb-0 text-success">{{$requests['paid']}}</h5>
                            </div>
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Payment in Progress Request</p>
                                <h5 class="mb-0 text-warning">{{$requests['payment_in_progress']}}</h5>
                            </div>
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Pending Request</p>
                                <h5 class="mb-0 text-warning">{{$requests['pending']}}</h5>
                            </div>
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Completed Request</p>
                                <h5 class="mb-0 text-primary">{{$requests['completed']}}</h5>
                            </div>
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-body shadow p-4">
        <h5 class="card-title mb-4">Revenue Generated Today</h5>    
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Today's Revenue</p>
                        <h4 class="text-success mb-0">{{$revenue['today']}}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted small mb-1">This Month's Revenue</p>
                        <h4 class="text-success mb-0">{{$revenue['this_month']}}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Total Revenue</p>
                        <h4 class="text-success mb-0">{{$revenue['total']}}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

