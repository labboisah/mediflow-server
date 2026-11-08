@if($patient->currentVisit()->visitActivities()->latest()->count() > 0)
    <h5 class="mb-3">Recent Activities</h5> 
<ol>
    @foreach($patient->currentVisit()->visitActivities()->latest()->get() as $activity)
        <li class="mb-2">
            <div class="d-flex align-items-center gap-3">
                
                <div>
                    <p class="mb-0">{{ $activity->activity }}</p>
                    <small class="text-muted">{{ $activity->created_at->diffForHumans() }} by <span class="fw-bold">{{ $activity->recordedBy->name }}</span></small>
                </div>
            </div>
        </li>
    @endforeach
</ol>

@else
    <div class="alert alert-info small mb-0">No recent activities recorded for this visit.</div>    
@endif