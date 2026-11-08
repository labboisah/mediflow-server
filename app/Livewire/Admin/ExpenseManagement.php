<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class ExpenseManagement extends Component
{
    use WithPagination;

    public function boot(): void
    {
        app(\App\Services\PackageFinanceAccess::class)->authorize();
    }

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public string $category = '';
    public string $department = '';
    public string $createdBy = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'expense_date';
    public string $sortDirection = 'desc';

    public ?int $editingExpenseId = null;
    public string $expenseCategoryId = '';
    public string $departmentId = '';
    public string $title = '';
    public string $amount = '';
    public string $expenseDate = '';
    public string $description = '';

    private array $sortableFields = [
        'department' => 'departments.name',
        'category' => 'expense_categories.name',
        'title' => 'expenses.title',
        'amount' => 'expenses.amount',
        'expense_date' => 'expenses.expense_date',
        'created_by' => 'users.name',
        'created_at' => 'expenses.created_at',
    ];

    public function mount(): void
    {
        $this->expenseDate = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingDepartment(): void
    {
        $this->resetPage();
    }

    public function updatingCreatedBy(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (!array_key_exists($field, $this->sortableFields)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function edit(int $expenseId): void
    {
        abort_unless($this->canManageFinance(), 403);

        $expense = Expense::findOrFail($expenseId);

        $this->editingExpenseId = $expense->id;
        $this->expenseCategoryId = (string) $expense->expense_category_id;
        $this->departmentId = (string) $expense->department_id;
        $this->title = $expense->title;
        $this->amount = (string) $expense->amount;
        $this->expenseDate = optional($expense->expense_date)->format('Y-m-d') ?? (string) $expense->expense_date;
        $this->description = $expense->description ?? '';
    }

    public function save(): void
    {
        abort_unless($this->canManageFinance(), 403);

        $validated = $this->validate([
            'departmentId' => ['required', 'exists:departments,id'],
            'expenseCategoryId' => ['required', 'exists:expense_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expenseDate' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'department_id' => $validated['departmentId'],
            'expense_category_id' => $validated['expenseCategoryId'],
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expenseDate'],
            'description' => $validated['description'],
        ];

        if ($this->editingExpenseId) {
            Expense::findOrFail($this->editingExpenseId)->update($payload);
            $message = 'Expense updated successfully.';
        } else {
            Expense::create(array_merge($payload, [
                'created_by' => auth()->id(),
            ]));
            $message = 'Expense recorded successfully.';
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function delete(int $expenseId): void
    {
        abort_unless($this->canManageFinance(), 403);

        Expense::findOrFail($expenseId)->delete();

        if ($this->editingExpenseId === $expenseId) {
            $this->resetForm();
        }

        $this->dispatch('toast', message: 'Expense deleted successfully.', type: 'danger');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'department', 'createdBy', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingExpenseId',
            'expenseCategoryId',
            'departmentId',
            'title',
            'amount',
            'expenseDate',
            'description',
        ]);

        $this->expenseDate = now()->format('Y-m-d');
        $this->resetValidation();
    }

    public function render()
    {
        $query = Expense::query()
            ->select('expenses.*')
            ->with(['category', 'department', 'createdBy'])
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->leftJoin('departments', 'expenses.department_id', '=', 'departments.id')
            ->leftJoin('users', 'expenses.created_by', '=', 'users.id');

        if ($this->search !== '') {
            $search = $this->search;

            $query->where(function ($q) use ($search) {
                $q->where('expenses.title', 'like', "%{$search}%")
                    ->orWhere('expenses.description', 'like', "%{$search}%")
                    ->orWhere('expense_categories.name', 'like', "%{$search}%")
                    ->orWhere('departments.name', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        if ($this->category !== '') {
            $query->where('expenses.expense_category_id', $this->category);
        }

        if ($this->department !== '') {
            $query->where('expenses.department_id', $this->department);
        }

        if ($this->createdBy !== '') {
            $query->where('expenses.created_by', $this->createdBy);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('expenses.expense_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('expenses.expense_date', '<=', $this->dateTo);
        }

        $summaryQuery = clone $query;
        $sortColumn = $this->sortableFields[$this->sortField] ?? 'expenses.expense_date';

        return view('components.admin.expense-management', [
            'expenses' => $query
                ->orderBy($sortColumn, $this->sortDirection)
                ->paginate(15),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'users' => User::query()
                ->whereIn('id', Expense::query()->select('created_by')->whereNotNull('created_by'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'totalExpense' => (clone $summaryQuery)->sum('expenses.amount'),
            'recordCount' => (clone $summaryQuery)->count(),
            'averageExpense' => (clone $summaryQuery)->avg('expenses.amount') ?? 0,
            'categoryCount' => (clone $summaryQuery)->distinct('expenses.expense_category_id')->count('expenses.expense_category_id'),
            'canManageFinance' => $this->canManageFinance(),
            'pdfUrl' => route((request()->routeIs('finance.*') ? 'finance' : 'admin') . '.expenses.pdf', array_filter([
                'search' => $this->search ?: null,
                'category' => $this->category ?: null,
                'department' => $this->department ?: null,
                'created_by' => $this->createdBy ?: null,
                'date_from' => $this->dateFrom ?: null,
                'date_to' => $this->dateTo ?: null,
            ])),
        ]);
    }

    private function canManageFinance(): bool
    {
        return auth()->user()?->hasAnyRole(['administrator', 'finance_officer']) ?? false;
    }

    public function sortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return 'bi-arrow-down-up';
        }

        return $this->sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down';
    }
}

