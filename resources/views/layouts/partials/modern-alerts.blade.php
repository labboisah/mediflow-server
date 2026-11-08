@if(session('success'))
    <div class="mb-4 rounded-md border border-med-primary bg-white px-4 py-3 text-sm font-medium text-med-primary shadow-sm">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 rounded-md border border-med-danger bg-white px-4 py-3 text-sm font-medium text-med-danger shadow-sm">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 rounded-md border border-med-danger bg-white px-4 py-3 text-sm text-med-danger shadow-sm">
        <p class="font-semibold">Please review the highlighted fields.</p>
    </div>
@endif
