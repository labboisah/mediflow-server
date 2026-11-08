<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Investigations</h1>
            <p class="text-muted mb-0">Manage investigation setup for {{ auth()->user()->department?->name ?? 'your department' }}.</p>
        </div>
    </div>

    @if($feedbackMessage)
        <div class="alert alert-{{ $feedbackType }} alert-dismissible fade show" role="alert">
            {{ $feedbackMessage }}
            <button type="button" class="btn-close" wire:click="$set('feedbackMessage', null)"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">{{ $typeEditingId ? 'Edit Investigation Type' : 'Add Investigation Type' }}</h2>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="saveType">
                        <div class="mb-3">
                            <label class="form-label">Type Name</label>
                            <input type="text" class="form-control @error('typeName') is-invalid @enderror" wire:model="typeName" placeholder="Chemical Pathology, X-Ray">
                            @error('typeName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="typeIsActive" wire:model="typeIsActive">
                            <label class="form-check-label" for="typeIsActive">Active</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" wire:loading.attr="disabled" wire:target="saveType">
                                <i class="bi bi-save"></i> {{ $typeEditingId ? 'Update' : 'Save' }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="resetTypeForm">Clear</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Edit Investigation</h2>
                </div>
                <div class="card-body">
                    @if($investigationEditingId)
                        <form wire:submit.prevent="saveInvestigation">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select class="form-select @error('investigationTypeId') is-invalid @enderror" wire:model="investigationTypeId">
                                    <option value="">Select type</option>
                                    @foreach($typeOptions as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('investigationTypeId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Investigation Name</label>
                                <input type="text" class="form-control @error('investigationName') is-invalid @enderror" wire:model="investigationName">
                                @error('investigationName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Price</label>
                                    <input type="text" class="form-control" value="{{ $investigationPrice !== '' ? number_format((float) $investigationPrice, 2) : '0.00' }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Code</label>
                                    <input type="text" class="form-control @error('investigationCode') is-invalid @enderror" wire:model="investigationCode">
                                    @error('investigationCode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-success" wire:loading.attr="disabled" wire:target="saveInvestigation">
                                    <i class="bi bi-save"></i> Update
                                </button>
                                <button type="button" class="btn btn-outline-secondary" wire:click="resetInvestigationForm">Cancel</button>
                            </div>
                        </form>
                    @else
                        <div class="text-muted small">
                            Select an existing investigation from the list to edit its name, code, or type. Prices are locked.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">{{ $parameterEditingId ? 'Edit Parameter' : 'Add Parameter' }}</h2>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="saveParameter">
                        <div class="mb-3">
                            <label class="form-label">Investigation</label>
                            <select class="form-select @error('parameterInvestigationId') is-invalid @enderror" wire:model="parameterInvestigationId">
                                <option value="">Select investigation</option>
                                @foreach($investigationOptions as $investigation)
                                    <option value="{{ $investigation->id }}">
                                        {{ $investigation->name }} @if($investigation->investigationType) - {{ $investigation->investigationType->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('parameterInvestigationId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parameter Name</label>
                            <input type="text" class="form-control @error('parameterName') is-invalid @enderror" wire:model="parameterName">
                            @error('parameterName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control @error('parameterUnit') is-invalid @enderror" wire:model="parameterUnit" placeholder="mg/dL, %, mmHg">
                            @error('parameterUnit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference Range</label>
                            <input type="text" class="form-control @error('parameterReferenceRange') is-invalid @enderror" wire:model="parameterReferenceRange">
                            @error('parameterReferenceRange') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" wire:loading.attr="disabled" wire:target="saveParameter">
                                <i class="bi bi-save"></i> {{ $parameterEditingId ? 'Update' : 'Save' }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="resetParameterForm">Clear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <label class="form-label">Search</label>
                    <input type="search" class="form-control mb-3" wire:model.live.debounce.400ms="search" placeholder="Search type, investigation, or code">

                    <div class="accordion" id="departmentInvestigationAccordion">
                        @forelse($types as $type)
                            <div class="accordion-item" wire:key="type-{{ $type->id }}">
                                <h2 class="accordion-header" id="typeHeading{{ $type->id }}">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#typeCollapse{{ $type->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="typeCollapse{{ $type->id }}">
                                        <span class="fw-semibold">{{ $type->name }}</span>
                                        <span class="badge bg-{{ $type->is_active ? 'success' : 'secondary' }} ms-2">{{ $type->is_active ? 'Active' : 'Inactive' }}</span>
                                        <span class="badge bg-light text-dark ms-2">{{ $type->investigations->count() }} investigations</span>
                                    </button>
                                </h2>
                                <div id="typeCollapse{{ $type->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="typeHeading{{ $type->id }}">
                                    <div class="accordion-body">
                                        <div class="d-flex justify-content-end gap-2 mb-3">
                                            <button type="button" class="btn btn-sm btn-outline-warning" wire:click="editType({{ $type->id }})">
                                                <i class="bi bi-pencil"></i> Edit Type
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="deleteType({{ $type->id }})" wire:confirm="Delete this investigation type?">
                                                <i class="bi bi-trash"></i> Delete Type
                                            </button>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Investigation</th>
                                                        <th class="text-end">Price</th>
                                                        <th class="text-end">Requests</th>
                                                        <th class="text-end">Parameters</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($type->investigations as $investigation)
                                                        <tr wire:key="investigation-{{ $investigation->id }}">
                                                            <td>{{ $investigation->code ?: 'N/A' }}</td>
                                                            <td class="fw-semibold">{{ $investigation->name }}</td>
                                                            <td class="text-end">{{ $investigation->price !== null ? number_format((float) $investigation->price, 2) : '0.00' }}</td>
                                                            <td class="text-end">{{ number_format($investigation->investigation_requests_count) }}</td>
                                                            <td class="text-end">{{ number_format($investigation->parameters_count) }}</td>
                                                            <td class="text-end">
                                                                <div class="btn-group btn-group-sm">
                                                                    <button type="button" class="btn btn-outline-warning" wire:click="editInvestigation({{ $investigation->id }})" title="Edit investigation">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-outline-danger" wire:click="deleteInvestigation({{ $investigation->id }})" wire:confirm="Delete this investigation?" title="Delete investigation">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr wire:key="parameters-{{ $investigation->id }}">
                                                            <td></td>
                                                            <td colspan="5">
                                                                <div class="d-flex flex-wrap gap-2">
                                                                    @forelse($investigation->parameters as $parameter)
                                                                        <span class="badge rounded-pill bg-light text-dark border">
                                                                            {{ $parameter->name }}
                                                                            @if($parameter->unit)
                                                                                <span class="text-muted">({{ $parameter->unit }})</span>
                                                                            @endif
                                                                            <button type="button" class="btn btn-sm btn-link p-0 ms-1 text-warning" wire:click="editParameter({{ $parameter->id }})" title="Edit parameter">
                                                                                <i class="bi bi-pencil-square"></i>
                                                                            </button>
                                                                            <button type="button" class="btn btn-sm btn-link p-0 ms-1 text-danger" wire:click="deleteParameter({{ $parameter->id }})" wire:confirm="Delete this parameter?" title="Delete parameter">
                                                                                <i class="bi bi-x-circle"></i>
                                                                            </button>
                                                                        </span>
                                                                    @empty
                                                                        <span class="text-muted small">No parameters configured.</span>
                                                                    @endforelse
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted py-4">No investigations found for this type.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">
                                No investigation types found for this department.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
