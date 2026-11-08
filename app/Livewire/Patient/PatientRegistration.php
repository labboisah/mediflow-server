<?php

namespace App\Livewire\Patient;

use App\Models\FileType;
use App\Models\Lga;
use App\Models\NextOfKin;
use App\Models\Patient;
use App\Models\PatientDemographic;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.modern')]
class PatientRegistration extends Component
{
    public string $fileType = '';
    public bool $anc = false;
    public int $discount = 0;
    public bool $isWalkIn = false;

    public string $firstName = '';
    public string $lastName = '';
    public string $gender = '';
    public string $dateOfBirth = '';
    public string $stateId = '';
    public string $lga = '';
    public string $occupation = '';
    public string $maritalStatus = '';
    public string $phoneNumber = '';
    public string $email = '';
    public string $address = '';

    public string $nokName = '';
    public string $nokRelationship = '';
    public string $nokTelephone = '';
    public string $nokContactAddress = '';

    public function render()
    {
        return view('components.patient.patient-registration', [
            'fileTypes' => FileType::orderBy('name')->get(),
            'states' => State::orderBy('name')->get(),
            'lgas' => $this->stateId !== ''
                ? Lga::where('state_id', $this->stateId)->orderBy('name')->get()
                : collect(),
            'selectedFileType' => $this->fileType !== '' ? FileType::find($this->fileType) : null,
            'estimatedAge' => $this->estimatedAge(),
            'pageTitle' => 'Patient Registration',
            'pageSubtitle' => 'Create a patient record, capture demographics, and open the first visit.',
        ]);
    }

    public function updatedStateId(): void
    {
        $this->lga = '';
    }

    public function updatedPhoneNumber(): void
    {
        if (strlen(trim($this->phoneNumber)) < 7) {
            return;
        }

        if (PatientDemographic::where('phone_number', trim($this->phoneNumber))->exists()) {
            $this->addError('phoneNumber', 'A patient with this phone number already exists.');
        } else {
            $this->resetErrorBag('phoneNumber');
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'fileType' => ['required', 'exists:file_types,id'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:Male,Female,Other'],
            'dateOfBirth' => ['required', 'date', 'before_or_equal:today'],
            'lga' => ['nullable', 'exists:lgas,id'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'maritalStatus' => ['nullable', 'in:Single,Married,Divorced,Widowed'],
            'address' => ['nullable', 'string', 'max:500'],
            'phoneNumber' => ['required', 'string', 'max:30', 'unique:patient_demographics,phone_number'],
            'email' => ['nullable', 'email', 'unique:patient_demographics,email'],
            'discount' => ['required', 'integer', 'min:0', 'max:100'],
            'nokName' => ['required', 'string', 'max:255'],
            'nokRelationship' => ['required', 'string', 'max:255'],
            'nokContactAddress' => ['nullable', 'string', 'max:500'],
            'nokTelephone' => ['required', 'string', 'max:20'],
        ]);

        try {
            $patient = DB::transaction(function () use ($validated) {
                $patient = Patient::create([
                    'file_type_id' => $validated['fileType'],
                    'hospital_number' => Patient::generateHospitalNumber(),
                    'registration_date' => now(),
                    'is_walkIn' => $this->isWalkIn,
                ]);

                PatientDemographic::create([
                    'patient_id' => $patient->id,
                    'first_name' => strtoupper($validated['firstName']),
                    'last_name' => strtoupper($validated['lastName']),
                    'gender' => $validated['gender'],
                    'date_of_birth' => $validated['dateOfBirth'],
                    'age' => Carbon::parse($validated['dateOfBirth'])->age,
                    'lga_id' => $validated['lga'] ?: null,
                    'occupation' => $validated['occupation'] ?: null,
                    'marital_status' => $validated['maritalStatus'] ?: null,
                    'address' => $validated['address'] ?: null,
                    'phone_number' => $validated['phoneNumber'],
                    'email' => $validated['email'] ?: null,
                ]);

                NextOfKin::create([
                    'patient_id' => $patient->id,
                    'name' => $validated['nokName'],
                    'relationship' => $validated['nokRelationship'],
                    'contact_address' => $validated['nokContactAddress'] ?: null,
                    'telephone' => $validated['nokTelephone'],
                ]);

                $visit = $patient->registerNewVisit();
                $patient->generateFileOpeningBill($visit, (float) $validated['discount'], $this->anc);

                $visit->visitActivities()->create([
                    'recorded_by' => auth()->id(),
                    'activity' => 'Patient Registered',
                ]);

                $visit->visitActivities()->create([
                    'recorded_by' => auth()->id(),
                    'activity' => 'Visit Registered',
                ]);

                return $patient->fresh('demographic');
            });

            $this->dispatch('toast', message: 'Patient registered successfully.', type: 'success');

            return redirect()
                ->route('patient.show', $patient)
                ->with('success', "Patient {$patient->demographic->full_name} registered successfully with Hospital Number: {$patient->hospital_number}");
        } catch (Throwable $exception) {
            report($exception);

            $message = $this->registrationFailureMessage($exception);

            $this->addError('registration', $message);
            $this->dispatch('toast', message: $message, type: 'danger');

            return null;
        }
    }

    private function estimatedAge(): ?int
    {
        if ($this->dateOfBirth === '') {
            return null;
        }

        try {
            return Carbon::parse($this->dateOfBirth)->age;
        } catch (Throwable) {
            return null;
        }
    }

    private function registrationFailureMessage(Throwable $exception): string
    {
        if ($exception instanceof QueryException) {
            $databaseMessage = $exception->getMessage();

            if (str_contains($databaseMessage, 'patient_demographics_phone_number_unique')) {
                return 'A patient with this phone number already exists.';
            }

            if (str_contains($databaseMessage, 'patient_demographics_email_unique')) {
                return 'A patient with this email address already exists.';
            }

            if (str_contains($databaseMessage, 'Duplicate entry')) {
                return 'A duplicate patient detail was found. Please check the phone number, email address, or hospital number.';
            }

            if (str_contains($databaseMessage, 'foreign key constraint')) {
                return 'A selected record could not be linked. Please reselect the file type, state, or local government and try again.';
            }
        }

        $message = trim($exception->getMessage());

        if ($message !== '') {
            return 'Patient registration failed: ' . $message;
        }

        return 'Patient registration failed because an unknown system error occurred. Please contact the administrator.';
    }
}


