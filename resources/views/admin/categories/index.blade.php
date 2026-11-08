@extends('layouts.modern')

@section('page-title', 'Service Categories')

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.categories.create') }}"
       class="bg-accent text-white px-5 py-2 rounded-lg">
        + Add Category
    </a>
</div>

<div class="bg-white rounded-xl shadow p-6">

    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="py-3">Name</th>
                <th>Services</th>
                <th>Revenue Sharing</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>

        <tbody>
            @foreach($categories as $category)
                <tr class="border-b hover:bg-lightbg">
                    <td class="py-3 font-medium">{{ $category->name }}</td>
                    <td>{{ $category->services->count() }}</td>
                    <td class="text-xs">
                        @if($category->revenueRule)
                            <div class="bg-blue-50 p-2 rounded inline-block">
                                <p>Radiologist: {{ $category->revenueRule->radiologist_percent }}%</p>
                                <p>Radiographer: {{ $category->revenueRule->radiographer_percent }}%</p>
                                <p>Staff: {{ $category->revenueRule->staff_percent }}%</p>
                                <p>Annex: {{ $category->revenueRule->annex_percent }}%</p>
                            </div>
                        @else
                            <span class="text-gray-500 italic">Not configured</span>
                        @endif
                    </td>
                    <td class="text-right space-x-2">
                        <a href="{{ route('admin.categories.edit', $category) }}"
                           class="inline-block text-blue-600 hover:text-blue-800 font-semibold text-xs bg-blue-50 px-2 py-1 rounded">
                            ✏️ Edit
                        </a>

                        <form action="{{ route('admin.categories.destroy', $category) }}"
                              method="POST"
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 hover:text-red-800 font-semibold text-xs bg-red-50 px-2 py-1 rounded" onclick="return confirm('Are you sure you want to delete this category of services?')">
                                🗑️ Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection
