@extends('layouts.modern')

@section('title', 'Edit Category')
@section('page-title', 'Edit Service Category')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('admin.categories.index') }}"
           class="text-sm text-primary hover:underline">
            ← Back to Categories
        </a>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-lg p-8">

        <h2 class="text-2xl font-bold text-primary mb-6">
            Edit Category
        </h2>

        <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Category Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Category Name
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $category->name) }}"
                       class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

                @error('name')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Revenue Sharing Section -->
            <div class="bg-blue-50 p-6 rounded-xl border-2 border-blue-200">
                <h3 class="text-lg font-bold text-primary mb-4">💰 Revenue Sharing Configuration</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- Radiologist -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Radiologist Share (%)
                        </label>
                        <input type="number"
                               name="radiologist_percent"
                               step="0.01"
                               min="0"
                               max="100"
                               value="{{ old('radiologist_percent', $category->revenueRule->radiologist_percent ?? 0) }}"
                               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none"
                               oninput="updateTotal()">
                        @error('radiologist_percent')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Radiographer -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Radiographer Share (%)
                        </label>
                        <input type="number"
                               name="radiographer_percent"
                               step="0.01"
                               min="0"
                               max="100"
                               value="{{ old('radiographer_percent', $category->revenueRule->radiographer_percent ?? 0) }}"
                               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none"
                               oninput="updateTotal()">
                        @error('radiographer_percent')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Staff -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Staff Share (%)
                        </label>
                        <input type="number"
                               name="staff_percent"
                               step="0.01"
                               min="0"
                               max="100"
                               value="{{ old('staff_percent', $category->revenueRule->staff_percent ?? 0) }}"
                               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none"
                               oninput="updateTotal()">
                        @error('staff_percent')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Annex -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Annex Share (%)
                        </label>
                        <input type="number"
                               name="annex_percent"
                               step="0.01"
                               min="0"
                               max="100"
                               value="{{ old('annex_percent', $category->revenueRule->annex_percent ?? 0) }}"
                               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none"
                               oninput="updateTotal()">
                        @error('annex_percent')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Total Indicator -->
                <div class="bg-white p-4 rounded-lg mt-4 border-2 border-gray-200">
                    <p class="text-sm font-semibold text-gray-700">Total: <span id="totalPercent" class="text-lg font-bold text-blue-600">0%</span></p>
                    <div id="totalWarning" class="text-sm text-red-600 mt-2 hidden">⚠️ Total must equal 100%</div>
                    <div id="totalSuccess" class="text-sm text-green-600 mt-2 hidden">✓ Total is correct</div>
                </div>
                
                @error('total')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex justify-between items-center">

                

                <!-- Update -->
                <button type="submit"
                        class="bg-secondary text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary transition shadow">
                    Update Category
                </button>

            </div>

        </form>

        <!-- Delete -->
        <form action="{{ route('admin.categories.destroy', $category) }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to delete this category?')">
            @csrf
            @method('DELETE')

            <button class="text-red-600 hover:underline text-sm">
                Delete Category
            </button>
        </form>

    </div>

</div>

<script>
    function updateTotal() {
        const radiologist = parseFloat(document.querySelector('input[name="radiologist_percent"]').value || 0);
        const radiographer = parseFloat(document.querySelector('input[name="radiographer_percent"]').value || 0);
        const staff = parseFloat(document.querySelector('input[name="staff_percent"]').value || 0);
        const annex = parseFloat(document.querySelector('input[name="annex_percent"]').value || 0);
        
        const total = radiologist + radiographer + staff + annex;
        const totalPercent = document.getElementById('totalPercent');
        const warning = document.getElementById('totalWarning');
        const success = document.getElementById('totalSuccess');
        
        totalPercent.textContent = total.toFixed(2) + '%';
        
        if (Math.abs(total - 100) < 0.01) {
            totalPercent.classList.remove('text-red-600');
            totalPercent.classList.add('text-green-600');
            warning.classList.add('hidden');
            success.classList.remove('hidden');
        } else {
            totalPercent.classList.remove('text-green-600');
            totalPercent.classList.add('text-red-600');
            warning.classList.remove('hidden');
            success.classList.add('hidden');
        }
    }
    
    // Initialize on load
    updateTotal();
</script>

@endsection
