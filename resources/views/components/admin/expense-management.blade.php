<div class="space-y-6">
    <x-ui.page title="Expense Management" subtitle="Record and monitor hospital spending across departments.">
        <x-slot name="actions">
            <div class="flex flex-wrap gap-2">
                <a href="{{ $pdfUrl }}" target="_blank" class="inline-flex items-center justify-center rounded-md border border-med-danger bg-white px-3 py-2 text-sm font-semibold text-med-danger transition hover:bg-red-50">Download PDF</a>
                @if(auth()->user()?->hasRole('administrator'))
                    <a href="{{ route('admin.expense-categories.index') }}" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas">Manage Categories</a>
                @endif
                @if($canManageFinance)
                    <button type="button" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark" wire:click="resetForm">Add Expense</button>
                @endif
            </div>
        </x-slot>

        <div class="grid gap-4 md:grid-cols-4">
            <x-ui.card><p class="text-sm text-med-muted">Total Expense</p><p class="mt-2 text-2xl font-semibold text-med-danger">{{ number_format($totalExpense, 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm text-med-muted">Records</p><p class="mt-2 text-2xl font-semibold text-med-ink">{{ number_format($recordCount) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm text-med-muted">Average Expense</p><p class="mt-2 text-2xl font-semibold text-med-primary">{{ number_format($averageExpense, 2) }}</p></x-ui.card>
            <x-ui.card><p class="text-sm text-med-muted">Categories</p><p class="mt-2 text-2xl font-semibold text-med-warning">{{ number_format($categoryCount) }}</p></x-ui.card>
        </div>

        <x-ui.card title="Filters">
            <div class="grid gap-3 md:grid-cols-7 md:items-end">
                <label class="md:col-span-2"><span class="mb-1 block text-sm font-medium text-med-ink">Search</span><input type="search" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model.live.debounce.400ms="search" placeholder="Title, category, department, user"></label>
                <label><span class="mb-1 block text-sm font-medium text-med-ink">Category</span><select class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model.live="category"><option value="">All Categories</option>@foreach($categories as $expenseCategory)<option value="{{ $expenseCategory->id }}">{{ $expenseCategory->name }}</option>@endforeach</select></label>
                <label><span class="mb-1 block text-sm font-medium text-med-ink">Department</span><select class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model.live="department"><option value="">All Departments</option>@foreach($departments as $departmentOption)<option value="{{ $departmentOption->id }}">{{ $departmentOption->name }}</option>@endforeach</select></label>
                <label><span class="mb-1 block text-sm font-medium text-med-ink">Recorded By</span><select class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model.live="createdBy"><option value="">All Users</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></label>
                <label><span class="mb-1 block text-sm font-medium text-med-ink">From</span><input type="date" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model.live="dateFrom"></label>
                <label><span class="mb-1 block text-sm font-medium text-med-ink">To</span><input type="date" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model.live="dateTo"></label>
                <button type="button" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas" wire:click="resetFilters">Clear</button>
            </div>
        </x-ui.card>

        @if($canManageFinance)
            <x-ui.card title="{{ $editingExpenseId ? 'Edit Expense' : 'Add Expense' }}">
                <form wire:submit="save" class="grid gap-4 md:grid-cols-4">
                    <label><span class="mb-1 block text-sm font-medium text-med-ink">Department</span><select class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model="departmentId"><option value="">Select Department</option>@foreach($departments as $departmentOption)<option value="{{ $departmentOption->id }}">{{ $departmentOption->name }}</option>@endforeach</select>@error('departmentId')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</label>
                    <label><span class="mb-1 block text-sm font-medium text-med-ink">Category</span><select class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model="expenseCategoryId"><option value="">Select Category</option>@foreach($categories as $expenseCategory)<option value="{{ $expenseCategory->id }}">{{ $expenseCategory->name }}</option>@endforeach</select>@error('expenseCategoryId')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</label>
                    <label><span class="mb-1 block text-sm font-medium text-med-ink">Title</span><input type="text" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model="title">@error('title')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</label>
                    <label><span class="mb-1 block text-sm font-medium text-med-ink">Amount</span><input type="number" step="0.01" min="0" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model="amount">@error('amount')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</label>
                    <label><span class="mb-1 block text-sm font-medium text-med-ink">Expense Date</span><input type="date" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model="expenseDate">@error('expenseDate')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</label>
                    <label class="md:col-span-3"><span class="mb-1 block text-sm font-medium text-med-ink">Description</span><input type="text" class="mf-focus block w-full rounded-md border border-med-line px-3 py-2.5 text-sm text-med-ink shadow-sm" wire:model="description">@error('description')<p class="mt-1 text-sm text-med-danger">{{ $message }}</p>@enderror</label>
                    <div class="flex flex-wrap gap-2 md:col-span-4"><button type="submit" class="inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $editingExpenseId ? 'Save Changes' : 'Save Expense' }}</span><span wire:loading wire:target="save">Saving...</span></button><button type="button" class="inline-flex items-center justify-center rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-ink transition hover:bg-med-canvas" wire:click="resetForm">Cancel</button></div>
                </form>
            </x-ui.card>
        @endif

        <x-ui.card title="All Expenses">
            <x-ui.table>
                <thead class="bg-med-canvas text-left text-xs font-semibold uppercase text-med-muted"><tr><th class="px-4 py-3"><button type="button" wire:click="sortBy('department')">Department</button></th><th class="px-4 py-3"><button type="button" wire:click="sortBy('category')">Category</button></th><th class="px-4 py-3"><button type="button" wire:click="sortBy('title')">Title</button></th><th class="px-4 py-3 text-right"><button type="button" wire:click="sortBy('amount')">Amount</button></th><th class="px-4 py-3"><button type="button" wire:click="sortBy('expense_date')">Date</button></th><th class="px-4 py-3"><button type="button" wire:click="sortBy('created_by')">Recorded By</button></th>@if($canManageFinance)<th class="px-4 py-3 text-right">Actions</th>@endif</tr></thead>
                <tbody class="divide-y divide-med-line bg-white">@forelse($expenses as $expense)<tr wire:key="expense-{{ $expense->id }}"><td class="px-4 py-4 text-med-muted">{{ $expense->department->name ?? 'N/A' }}</td><td class="px-4 py-4"><x-ui.badge variant="info">{{ $expense->category->name ?? 'N/A' }}</x-ui.badge></td><td class="px-4 py-4"><div class="font-semibold text-med-ink">{{ $expense->title }}</div>@if($expense->description)<div class="text-sm text-med-muted">{{ \Illuminate\Support\Str::limit($expense->description, 50) }}</div>@endif</td><td class="px-4 py-4 text-right font-semibold text-med-danger">{{ number_format($expense->amount, 2) }}</td><td class="px-4 py-4 text-med-muted">{{ $expense->expense_date?->format('M d, Y') ?? 'N/A' }}</td><td class="px-4 py-4 text-med-muted">{{ $expense->createdBy->name ?? 'System' }}</td>@if($canManageFinance)<td class="px-4 py-4"><div class="flex justify-end gap-2"><button type="button" class="text-sm font-semibold text-med-info hover:text-blue-700" wire:click="edit({{ $expense->id }})">Edit</button><button type="button" class="text-sm font-semibold text-med-danger hover:text-red-800" wire:click="delete({{ $expense->id }})" wire:confirm="Delete this expense?">Delete</button></div></td>@endif</tr>@empty<tr><td colspan="{{ $canManageFinance ? 7 : 6 }}" class="px-4 py-8"><x-ui.empty-state title="No expenses found" message="Try adjusting the filters." /></td></tr>@endforelse</tbody>
            </x-ui.table>
            @if($expenses->hasPages())<div class="mt-4">{{ $expenses->links() }}</div>@endif
        </x-ui.card>
    </x-ui.page>
</div>
