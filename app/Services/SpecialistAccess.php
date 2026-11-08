<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\SpecialistConsultation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SpecialistAccess
{
    public function require(string $permission): User
    {
        $user = auth()->user();
        abort_unless($user && app(PackageCapabilities::class)->available('specialist')
            && app(LicenseService::class)->userHasModuleAccess($user, 'specialist')
            && $user->hasPermission($permission), 403);

        return $user;
    }

    public function patients(User $user): Builder
    {
        return Patient::whereIn('id', DB::table('specialist_patient_access')->where('user_id', $user->id)->select('patient_id'));
    }

    public function consultations(User $user): Builder
    {
        return SpecialistConsultation::whereIn('patient_id', $this->patients($user)->select('patients.id'));
    }

    public function consultation(int $id, string $permission): SpecialistConsultation
    {
        $user = $this->require($permission);

        return $this->consultations($user)->findOrFail($id);
    }

    public function grant(int $patientId, int $userId): void
    {
        DB::table('specialist_patient_access')->updateOrInsert(['patient_id' => $patientId, 'user_id' => $userId],
            ['granted_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
