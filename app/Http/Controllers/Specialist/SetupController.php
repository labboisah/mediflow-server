<?php

namespace App\Http\Controllers\Specialist;

use App\Http\Controllers\Controller;
use App\Models\CollaborationPartner;
use App\Models\Department;
use App\Models\Medicine;
use App\Models\MedicineType;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\SpecialistService;
use App\Models\SpecialistSetting;
use App\Models\Specialty;
use App\Models\User;
use App\Services\SpecialistAccess;
use App\Services\SpecialistWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SetupController extends Controller
{
    public function __construct(private SpecialistAccess $access, private SpecialistWorkflow $workflow) {}

    public function index()
    {
        $this->access->require('specialist.settings.manage');

        return view('specialist.setup', [
            'specialties' => Specialty::orderBy('name')->get(), 'profiles' => SpecialistProfile::with('user', 'specialties')->get(),
            'users' => User::orderBy('name')->get(), 'departments' => Department::orderBy('name')->get(),
            'offerings' => SpecialistService::with('service', 'specialty')->get(), 'partners' => CollaborationPartner::orderBy('name')->get(),
            'assignments' => DB::table('specialist_patient_access')->join('patients', 'patients.id', '=', 'specialist_patient_access.patient_id')->join('users', 'users.id', '=', 'specialist_patient_access.user_id')->select('specialist_patient_access.*', 'patients.hospital_number', 'users.name')->orderByDesc('specialist_patient_access.id')->limit(100)->get(),
            'settings' => SpecialistSetting::find(1), 'medicineTypes' => MedicineType::orderBy('name')->get(),
        ]);
    }

    public function save(Request $r, string $type)
    {
        $this->access->require('specialist.settings.manage');
        $model = DB::transaction(function () use ($r, $type) {
            $id = $r->validate(['id' => ['nullable', 'integer']])['id'] ?? null;
            switch ($type) {
                case 'specialty':
                    $d = $r->validate(['name' => ['required', 'string', 'max:255', Rule::unique('specialties')->ignore($id)], 'description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['required', 'boolean']]);
                    $m = $id ? Specialty::findOrFail($id) : new Specialty;
                    $m->fill($d)->save();
                    break;
                case 'profile':
                    $d = $r->validate(['user_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at'), Rule::unique('specialist_profiles', 'user_id')->ignore($id)],
                        'title' => ['required', 'string', 'max:255'], 'registration' => ['nullable', 'string', 'max:255'], 'subspecialty' => ['nullable', 'string', 'max:255'],
                        'timezone' => ['required', 'timezone'], 'location' => ['nullable', 'string', 'max:255'], 'online' => ['required', 'boolean'], 'physical' => ['required', 'boolean'],
                        'is_active' => ['required', 'boolean'], 'specialties' => ['required', 'array', 'min:1'], 'specialties.*' => ['integer', 'exists:specialties,id'],
                        'days' => ['required', 'array', 'min:1'], 'days.*' => ['integer', 'between:1,7'], 'from' => ['required', 'date_format:H:i'], 'to' => ['required', 'date_format:H:i', 'after:from'],
                        'unavailable_dates' => ['nullable', 'string', 'max:2000']]);
                    if (! User::findOrFail($d['user_id'])->hasPermission('specialist_consultation.update')) {
                        throw ValidationException::withMessages(['user_id' => 'Assign Specialist clinical permissions to this account before creating its profile.']);
                    }
                    $dates = array_values(array_filter(array_map('trim', explode(',', $d['unavailable_dates'] ?? ''))));
                    validator(['dates' => $dates], ['dates.*' => ['date_format:Y-m-d']])->validate();
                    if (! $d['physical'] && ! $d['online']) {
                        throw ValidationException::withMessages(['online' => 'Enable physical or online consultations.']);
                    }
                    if ($d['physical'] && blank($d['location'])) {
                        throw ValidationException::withMessages(['location' => 'A physical location is required.']);
                    }
                    $m = $id ? SpecialistProfile::lockForUpdate()->findOrFail($id) : new SpecialistProfile;
                    if ($m->exists && (int) $m->user_id !== (int) $d['user_id']) {
                        throw ValidationException::withMessages(['user_id' => 'An existing specialist profile cannot be moved to another account.']);
                    }
                    $m->fill(collect($d)->except(['specialties', 'days', 'from', 'to', 'unavailable_dates'])->all());
                    $m->availability = ['days' => array_map('intval', $d['days']), 'from' => $d['from'], 'to' => $d['to'], 'unavailable_dates' => $dates];
                    $m->save();
                    $m->specialties()->sync($d['specialties']);
                    break;
                case 'service':
                    $d = $r->validate(['name' => ['required', 'string', 'max:255'], 'price' => ['required', 'numeric', 'between:0,99999999.99', 'decimal:0,2'],
                        'department_id' => ['required', 'integer', 'exists:departments,id'], 'specialty_id' => ['required', 'integer', 'exists:specialties,id'],
                        'duration_minutes' => ['required', 'integer', 'between:5,480'], 'is_active' => ['required', 'boolean']]);
                    $m = $id ? SpecialistService::findOrFail($id) : new SpecialistService;
                    $service = $id ? $m->service : new Service;
                    $service->fill(collect($d)->only(['name', 'price', 'department_id', 'is_active'])->all());
                    if (! $service->exists) {
                        $service->code = 'SP-'.Str::ulid();
                        $service->category = 'Specialist';
                    }
                    $service->save();
                    $m->fill(['service_id' => $service->id, 'specialty_id' => $d['specialty_id'], 'duration_minutes' => $d['duration_minutes']])->save();
                    break;
                case 'partner':
                    $d = $r->validate(['name' => ['required', 'string', 'max:255'], 'type' => ['required', Rule::in(['hospital', 'clinic', 'diagnostics', 'pharmacy', 'specialist', 'other'])],
                        'contact' => ['nullable', 'string', 'max:255'], 'is_active' => ['required', 'boolean']]);
                    $m = $id ? CollaborationPartner::findOrFail($id) : new CollaborationPartner;
                    abort_if($m->is_local, 403, 'The local installation identity is managed through system setup.');
                    abort_if($m->exists && \Illuminate\Support\Facades\Schema::hasTable('partnerships') && \App\Models\Partnership::where('partner_id',$m->id)->exists(),403,'Manage network providers through Care Network so changes are reviewed.');
                    $m->fill($d)->save();
                    break;
                case 'settings':
                    $d = $r->validate(['allow_walkins' => ['required', 'boolean'], 'operating_mode' => ['required', Rule::in(['physical', 'online', 'hybrid'])]]);
                    $m = SpecialistSetting::firstOrNew(['id' => 1]);
                    $m->fill($d)->save();
                    break;
                case 'medicine':
                    $d = $r->validate(['name' => ['required', 'string', 'max:255', Rule::unique('medicines')], 'medicine_type_id' => ['required', 'integer', 'exists:medicine_types,id']]);
                    $m = Medicine::create($d);
                    break;
                default: abort(404);
            }
            $this->workflow->audit('configuration_updated', $m, ['section' => $type]);

            return $m;
        });

        return back()->with('success', 'Configuration saved. Existing records retain their history.');
    }
}
