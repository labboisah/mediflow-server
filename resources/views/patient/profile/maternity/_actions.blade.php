<div class="d-flex justify-content-end gap-1">
    <a href="{{ route($showRoute, $record) }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-eye"></i> View
    </a>
    <a href="{{ route($editRoute, $record) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-pencil-square"></i> Edit
    </a>
    <form action="{{ route($destroyRoute, $record) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this {{ $recordLabel }} record?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash"></i> Delete
        </button>
    </form>
</div>
