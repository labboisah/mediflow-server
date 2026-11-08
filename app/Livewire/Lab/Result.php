<?php

namespace App\Livewire\Lab;

use Livewire\Component;
use Livewire\Attributes\Layout;

use App\Models\InvestigationRequest;
use App\Models\InvestigationResult;
use App\Models\Bill;

#[Layout('layouts.modern')]
class Result extends Component
{
    public string $bill_number = '';

    public $requests = [];

    public $patient = null;

    public $walkin = null;

    public $bill = null;

    public array $labNumbers = [];

    public array $results = [];

    public bool $loaded = false;

    // public function updatedBillNumber()
    // {
    //     if (strlen(trim($this->bill_number)) >= 3) {
    //         $this->loadRequest();
    //     } else {
    //         $this->resetRequestData();
    //     }
    // }

    public function search()
    {
        if (blank($this->bill_number)) {

            $this->dispatch(
                'toast',
                type: 'warning',
                message: 'Please enter a Bill Number.'
            );

            return;
        }

        $this->loadRequest();

        

    }


    public function resetRequestData()
    {
        $this->requests = [];
        $this->patient = null;
        $this->results = [];
        $this->loaded = false;
    }

    public function loadRequest()
    {
        $this->resetRequestData();

        $bill = Bill::with([
            'investigationRequests.investigation.parameters',
            'investigationRequests.investigationResults',
            'investigationRequests.patientVisit.patient',
            'investigationRequests.walkinPatient',
        ])
        ->where(
            'bill_number',
            trim($this->bill_number)
        )
        ->first();

        if (!$bill) {
            return;
        }
        
        $this->bill = $bill;
        $requests = $bill->investigationRequests;

        if ($requests->isEmpty()) {
            return;
        }
        
        $this->requests = $requests;
        $this->loaded = true;

        $firstRequest = $requests->first();

        if ($firstRequest->patientVisit) {

            $this->patient =
                $firstRequest->patientVisit->patient;

        } elseif ($firstRequest->walkinPatient) {

            $this->walkin =
                $firstRequest->walkinPatient;
        }

        foreach ($requests as $request) {

            $this->labNumbers[$request->id] =
                $request->lab_no;

            foreach (
                $request->investigationResults
                as $result
            ) {

                $this->results[
                    $request->id
                ][
                    $result->parameter_id
                ] = $result->value;
            }
        }
    }

    public function saveInvestigation($requestId)
    {
        $request = collect($this->requests)
            ->firstWhere('id', $requestId);

        if (!$request) {
            return;
        }
        

        foreach ($request->investigation->parameters as $parameter) {

            $value =
                $this->results[$requestId][$parameter->id]
                ?? null;
            
            if (blank($value)) {
                continue;
            }

            $result = InvestigationResult::updateOrCreate(
                [
                    'investigation_request_id' => $requestId,
                    'parameter_id' => $parameter->id,
                ],
                [
                    'value' => $value,
                ]
            );
            
        }

        $requestModel = InvestigationRequest::find($requestId);

        $requestModel->update([
            'lab_no' =>
                $this->labNumbers[$requestId]
                    ?? $requestModel->lab_no,

            'status' => 'Completed',
            'performed_by' => auth()->id(),
            'completed_at' => now(),
        ]);
        
        $this->loadRequest();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Result saved successfully'
        );
    }

    public function deleteResult($requestId, $parameterId)
    {
        InvestigationResult::where(
            'investigation_request_id',
            $requestId
        )
        ->where(
            'parameter_id',
            $parameterId
        )
        ->delete();

        $this->loadRequest();

        $this->dispatch('$refresh');

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Result deleted successfully.'
        );
    }

    public function render()
    {
        
        return view('components.lab.result');
    }
}

