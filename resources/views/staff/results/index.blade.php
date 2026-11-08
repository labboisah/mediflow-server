@extends('layouts.modern')

@section('page-title', 'Result Entry')

@section('content')
<div class="max-w-3xl mx-auto bg-white p-8 rounded-2xl shadow">
    <h2 class="text-2xl font-bold text-primary mb-2">Result Entry</h2>
    <p class="text-sm text-gray-500 mb-6">Enter a fully paid bill number to open its investigation result page.</p>

    <form method="POST" action="{{ route('staff.results.lookup') }}" class="space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-2">Bill Number</label>
            <input type="text" name="bill_no" value="{{ old('bill_no') }}"
                   class="w-full px-4 py-3 border rounded-xl"
                   placeholder="Example: B260719-1234">
            @error('bill_no')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
        </div>

        <button class="bg-accent text-white px-8 py-3 rounded-xl">Continue</button>
    </form>
</div>
@endsection

