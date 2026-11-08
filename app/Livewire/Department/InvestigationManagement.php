<?php

namespace App\Livewire\Department;

use App\Models\Investigation;
use App\Models\InvestigationType;
use App\Models\Parameter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class InvestigationManagement extends Component
{
    public string $search = '';
    public ?int $typeEditingId = null;
    public string $typeName = '';
    public bool $typeIsActive = true;

    public ?int $investigationEditingId = null;
    public string $investigationTypeId = '';
    public string $investigationName = '';
    public string $investigationPrice = '';
    public string $investigationCode = '';

    public ?int $parameterEditingId = null;
    public string $parameterInvestigationId = '';
    public string $parameterName = '';
    public string $parameterUnit = '';
    public string $parameterReferenceRange = '';

    public ?string $feedbackMessage = null;
    public string $feedbackType = 'success';

    public function render()
    {
        $this->authorizeInvestigationDepartment();

        $departmentId = auth()->user()->department_id;
        $search = trim($this->search);

        $types = InvestigationType::query()
            ->where('department_id', $departmentId)
            ->with(['investigations' => function ($query) use ($search) {
                $query
                    ->with(['parameters'])
                    ->withCount(['parameters', 'investigationRequests'])
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                    })
                    ->orderBy('name');
            }])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('investigations', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->get();

        return view('components.department.investigation-management', [
            'types' => $types,
            'typeOptions' => InvestigationType::query()
                ->where('department_id', $departmentId)
                ->orderBy('name')
                ->get(),
            'investigationOptions' => $this->departmentInvestigationsQuery()
                ->with('investigationType')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function saveType(): void
    {
        $this->authorizeInvestigationDepartment();

        $validated = $this->validate([
            'typeName' => ['required', 'string', 'max:255'],
            'typeIsActive' => ['boolean'],
        ]);

        $payload = [
            'department_id' => auth()->user()->department_id,
            'name' => $validated['typeName'],
            'is_active' => $validated['typeIsActive'],
        ];

        if ($this->typeEditingId) {
            $this->departmentTypesQuery()->findOrFail($this->typeEditingId)->update($payload);
            $this->setFeedback('Investigation type updated successfully.');
        } else {
            InvestigationType::create($payload);
            $this->setFeedback('Investigation type created successfully.');
        }

        $this->resetTypeForm();
    }

    public function editType(int $typeId): void
    {
        $type = $this->departmentTypesQuery()->findOrFail($typeId);

        $this->typeEditingId = $type->id;
        $this->typeName = $type->name;
        $this->typeIsActive = (bool) $type->is_active;
        $this->resetValidation();
    }

    public function deleteType(int $typeId): void
    {
        $type = $this->departmentTypesQuery()->withCount('investigations')->findOrFail($typeId);

        if ($type->investigations_count > 0) {
            $this->setFeedback('This type has investigations. Move or delete the investigations first.', 'warning');
            return;
        }

        $type->delete();
        $this->setFeedback('Investigation type deleted successfully.', 'danger');
    }

    public function saveInvestigation(): void
    {
        $this->authorizeInvestigationDepartment();

        if ($this->investigationEditingId === null) {
            $this->setFeedback('Select an existing investigation before saving changes.', 'warning');
            return;
        }

        $validated = $this->validate([
            'investigationTypeId' => ['required', 'integer'],
            'investigationName' => ['required', 'string', 'max:255'],
            'investigationCode' => ['nullable', 'string', 'max:255'],
        ]);

        $this->departmentTypesQuery()->findOrFail((int) $validated['investigationTypeId']);

        $payload = [
            'investigation_type_id' => (int) $validated['investigationTypeId'],
            'name' => $validated['investigationName'],
            'code' => $validated['investigationCode'] === '' ? null : $validated['investigationCode'],
        ];

        $this->departmentInvestigationsQuery()->findOrFail($this->investigationEditingId)->update($payload);
        $this->setFeedback('Investigation updated successfully.');

        $this->resetInvestigationForm();
    }

    public function editInvestigation(int $investigationId): void
    {
        $investigation = $this->departmentInvestigationsQuery()->findOrFail($investigationId);

        $this->investigationEditingId = $investigation->id;
        $this->investigationTypeId = (string) $investigation->investigation_type_id;
        $this->investigationName = $investigation->name;
        $this->investigationPrice = (string) $investigation->price;
        $this->investigationCode = (string) $investigation->code;
        $this->resetValidation();
    }

    public function deleteInvestigation(int $investigationId): void
    {
        $investigation = $this->departmentInvestigationsQuery()
            ->withCount(['investigationRequests', 'parameters'])
            ->findOrFail($investigationId);

        if ($investigation->investigation_requests_count > 0) {
            $this->setFeedback('This investigation already has patient requests, so it cannot be deleted.', 'warning');
            return;
        }

        if ($investigation->parameters_count > 0) {
            $this->setFeedback('Delete the investigation parameters before deleting this investigation.', 'warning');
            return;
        }

        $investigation->delete();
        $this->setFeedback('Investigation deleted successfully.', 'danger');
    }

    public function saveParameter(): void
    {
        $this->authorizeInvestigationDepartment();

        $validated = $this->validate([
            'parameterInvestigationId' => ['required', 'integer'],
            'parameterName' => ['required', 'string', 'max:255'],
            'parameterUnit' => ['nullable', 'string', 'max:255'],
            'parameterReferenceRange' => ['nullable', 'string', 'max:255'],
        ]);

        $investigation = $this->departmentInvestigationsQuery()->findOrFail((int) $validated['parameterInvestigationId']);

        $payload = [
            'investigation_id' => $investigation->id,
            'name' => $validated['parameterName'],
            'unit' => $validated['parameterUnit'] === '' ? null : $validated['parameterUnit'],
            'reference_range' => $validated['parameterReferenceRange'] === '' ? null : $validated['parameterReferenceRange'],
        ];

        if ($this->parameterEditingId) {
            $this->departmentParametersQuery()->findOrFail($this->parameterEditingId)->update($payload);
            $this->setFeedback('Parameter updated successfully.');
        } else {
            Parameter::create($payload);
            $this->setFeedback('Parameter created successfully.');
        }

        $this->resetParameterForm();
    }

    public function editParameter(int $parameterId): void
    {
        $parameter = $this->departmentParametersQuery()->findOrFail($parameterId);

        $this->parameterEditingId = $parameter->id;
        $this->parameterInvestigationId = (string) $parameter->investigation_id;
        $this->parameterName = $parameter->name;
        $this->parameterUnit = (string) $parameter->unit;
        $this->parameterReferenceRange = (string) $parameter->reference_range;
        $this->resetValidation();
    }

    public function deleteParameter(int $parameterId): void
    {
        $parameter = $this->departmentParametersQuery()
            ->withCount('investigationResults')
            ->findOrFail($parameterId);

        if ($parameter->investigation_results_count > 0) {
            $this->setFeedback('This parameter already has investigation results, so it cannot be deleted.', 'warning');
            return;
        }

        $parameter->delete();
        $this->setFeedback('Parameter deleted successfully.', 'danger');
    }

    public function resetTypeForm(): void
    {
        $this->reset(['typeEditingId', 'typeName']);
        $this->typeIsActive = true;
        $this->resetValidation();
    }

    public function resetInvestigationForm(): void
    {
        $this->reset(['investigationEditingId', 'investigationTypeId', 'investigationName', 'investigationPrice', 'investigationCode']);
        $this->resetValidation();
    }

    public function resetParameterForm(): void
    {
        $this->reset(['parameterEditingId', 'parameterInvestigationId', 'parameterName', 'parameterUnit', 'parameterReferenceRange']);
        $this->resetValidation();
    }

    private function setFeedback(string $message, string $type = 'success'): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackType = $type;
    }

    private function authorizeInvestigationDepartment(): void
    {
        abort_unless($this->canManageInvestigations(), 403);
    }

    private function canManageInvestigations(): bool
    {
        $department = auth()->user()->department;
        $departmentName = strtolower((string) $department?->name);

        return $department !== null
            && (
                str_contains($departmentName, 'lab')
                || str_contains($departmentName, 'radio')
                || $department->investigationTypes()->exists()
            );
    }

    private function departmentTypesQuery()
    {
        return InvestigationType::query()->where('department_id', auth()->user()->department_id);
    }

    private function departmentInvestigationsQuery()
    {
        return Investigation::query()
            ->whereHas('investigationType', fn ($query) => $query->where('department_id', auth()->user()->department_id));
    }

    private function departmentParametersQuery()
    {
        return Parameter::query()
            ->whereHas('investigation.investigationType', fn ($query) => $query->where('department_id', auth()->user()->department_id));
    }
}

