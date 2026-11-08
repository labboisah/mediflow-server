<a href="{{ route('accountant.bills.show', $bill) }}" class="btn btn-sm btn-info" title="View">
    <i class="bi bi-eye"></i>
</a>
@if($bill->canBeManagedAsUnpaidByAccountant(auth()->user()))
    <a href="{{ route('accountant.bills.edit', $bill) }}" class="btn btn-sm btn-warning" title="Edit">
        <i class="bi bi-pencil"></i>
    </a>
    @php($deleteBlockReason = $bill->softDeleteBlockReason())
    @if($deleteBlockReason)
        <button type="button" class="btn btn-sm btn-secondary" title="{{ $deleteBlockReason }}" disabled>
            <i class="bi bi-trash"></i>
        </button>
    @else
        <form action="{{ route('accountant.bills.delete', $bill) }}" method="POST" class="d-inline" onsubmit="return confirm('Soft delete this unpaid bill? It can be restored later.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    @endif
@endif
