<?php

namespace App\Services\Diagnostics;

use App\Models\DiagnosticBillContact;
use App\Models\DiagnosticImportReference;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\PatientDemographic;
use App\Models\WalkinPatient;
use App\Models\PatientVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolveDiagnosticPatient
{
    public function attachResolvedIdentityToBill(Bill $bill, Patient|WalkinPatient $target, array $identity, int $createdBy, mixed $visitDate = null): DiagnosticBillContact
    {
        return DB::transaction(function () use ($bill, $target, $identity, $createdBy, $visitDate) {
            if (! $bill->patient_visit_id && ! $bill->walkin_id) {
                if ($target instanceof Patient) {
                    $visit = PatientVisit::create([
                        'patient_id' => $target->id,
                        'visit_date' => $visitDate ?? $bill->issued_date ?? now(),
                        'visit_type' => 'Diagnostic Center',
                        'reason_for_visit' => 'Imported diagnostic bill',
                        'status' => 'Active',
                        'created_by' => $createdBy,
                    ]);
                    $bill->patient_visit_id = $visit->id;
                } else {
                    $bill->walkin_id = $target->id;
                }
                $bill->save();
            } elseif ($target instanceof Patient && (int) $bill->patientVisit?->patient_id !== (int) $target->id) {
                throw ValidationException::withMessages(['patient' => 'The diagnostic bill is already linked to a different patient.']);
            } elseif ($target instanceof WalkinPatient && (int) $bill->walkin_id !== (int) $target->id) {
                throw ValidationException::withMessages(['patient' => 'The diagnostic bill is already linked to a different walk-in patient.']);
            }

            return $this->attachToBill($bill, $identity);
        });
    }
    public function attachToBill(Bill $bill, array $identity, string $sourceSystem = 'medistop'): DiagnosticBillContact
    {
        return $bill->diagnosticContact()->updateOrCreate([], [
            'source_system' => $sourceSystem,
            'source_patient_id' => isset($identity['patient_id']) ? (string) $identity['patient_id'] : null,
            'patient_phone' => isset($identity['phone_number']) && trim((string) $identity['phone_number']) !== '' ? trim((string) $identity['phone_number']) : null,
            'hospital_number' => isset($identity['hospital_number']) && trim((string) $identity['hospital_number']) !== '' ? trim((string) $identity['hospital_number']) : null,
        ]);
    }
    /** Resolve by stable Medistop ID, hospital number, or phone; never by name. */
    private function boundToOtherSourcePatient(Patient|WalkinPatient $target, string $sourceSystem, string $sourceId): bool
    {
        if ($sourceId === '') return false;
        return DiagnosticImportReference::where('source_system', $sourceSystem)
            ->where('source_table', 'patients')->where('target_type', $target::class)
            ->where('target_id', $target->id)->where('source_id', '!=', $sourceId)->exists();
    }
    public function resolve(array $identity, string $sourceSystem = 'medistop'): Patient|WalkinPatient
    {
        $sourceId = isset($identity['patient_id']) ? trim((string) $identity['patient_id']) : '';
        $hospitalNumber = isset($identity['hospital_number']) ? trim((string) $identity['hospital_number']) : '';
        $phone = isset($identity['phone_number']) ? trim((string) $identity['phone_number']) : '';

        return DB::transaction(function () use ($identity, $sourceSystem, $sourceId, $hospitalNumber, $phone) {
            if ($sourceId !== '') {
                $reference = DiagnosticImportReference::where('source_system', $sourceSystem)
                    ->where('source_table', 'patients')->where('source_id', $sourceId)->lockForUpdate()->first();
                if ($reference) {
                    $targetClass = $reference->target_type;
                    if (! in_array($targetClass, [Patient::class, WalkinPatient::class], true)) {
                        throw ValidationException::withMessages(['patient' => "Source patient {$sourceId} has an unsupported target mapping."]);
                    }
                    $target = $targetClass::query()->find($reference->target_id);
                    if ($target) return $target;
                    throw ValidationException::withMessages(['patient' => "Source patient {$sourceId} is mapped to a missing target record."]);
                }
            }

            $matches = collect();
            $matchedBy = [];
            if ($hospitalNumber !== '') {
                $patient = Patient::where('hospital_number', $hospitalNumber)->first();
                if ($patient) {
                    $key = Patient::class.':'.$patient->id;
                    $matches->put($key, $patient);
                    $matchedBy[$key] = 'hospital_number';
                }
            }
            if ($phone !== '') {
                $digits = preg_replace('/\D+/', '', $phone);
                $variants = array_values(array_unique(array_filter([$phone, $digits, $digits ? '+'.$digits : null])));
                $demographic = PatientDemographic::with('patient')->whereIn('phone_number', $variants)->first();
                if ($demographic?->patient && ! $this->boundToOtherSourcePatient($demographic->patient, $sourceSystem, $sourceId)) {
                    $key = Patient::class.':'.$demographic->patient->id;
                    $matches->put($key, $demographic->patient);
                    $matchedBy[$key] = 'phone_number';
                }
                $walkins = WalkinPatient::whereIn('phone_number', $variants)->limit(2)->get();
                foreach ($walkins as $walkin) {
                    if ($this->boundToOtherSourcePatient($walkin, $sourceSystem, $sourceId)) continue;
                    $key = WalkinPatient::class.':'.$walkin->id;
                    $matches->put($key, $walkin);
                    $matchedBy[$key] = 'phone_number';
                }
            }

            if ($matches->count() > 1) {
                throw ValidationException::withMessages(['patient' => 'Hospital number or phone identifies multiple target records. Review this source record before import.']);
            }

            $target = $matches->first();
            $method = $target ? ($matchedBy[$matches->keys()->first()] ?? 'identifier') : 'walkin_created';
            if (! $target) {
                $target = WalkinPatient::create([
                    'name' => $identity['name'] ?? null,
                    'phone_number' => $phone !== '' ? $phone : null,
                    'address' => $identity['address'] ?? null,
                ]);
            }

            if ($sourceId !== '') {
                DiagnosticImportReference::create([
                    'source_system' => $sourceSystem,
                    'source_table' => 'patients',
                    'source_id' => $sourceId,
                    'target_type' => $target::class,
                    'target_id' => $target->id,
                    'metadata' => ['match_method' => $method, 'hospital_number' => $hospitalNumber ?: null, 'phone_number' => $phone ?: null],
                ]);
            }

            return $target;
        });
    }
}






