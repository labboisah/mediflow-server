@if(session('success'))<div role="status" class="rounded-md border border-med-primary p-4 text-med-primary">{{ session('success') }}</div>@endif
@if($errors->any())<div role="alert" class="rounded-md border border-red-200 p-4 text-med-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
