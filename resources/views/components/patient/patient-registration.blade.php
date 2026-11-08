<x-ui.page :title="$pageTitle ?? 'Workspace'" :subtitle="$pageSubtitle ?? config('app.name')">
    <x-slot:actions>
        <a href="{{ route('record.patients.index') }}">
            <x-ui.button type="button" variant="secondary">
                <i class="bi bi-arrow-left"></i>
                Back to Patients
            </x-ui.button>
        </a>
    </x-slot:actions>

    @error('registration')
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i class="bi bi-exclamation-triangle"></i>
            {{ $message }}
        </div>
    @enderror

    <form wire:submit="save">
        <div class="grid gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-8">
                <x-ui.card title="Patient Information">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.select label="File Type" name="fileType" wire:model.live="fileType" required>
                            <option value="">Select file type</option>
                            @foreach($fileTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }} (NGN {{ number_format($type->price, 2) }})</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select label="Discount" name="discount" wire:model.live="discount">
                            @for($i = 0; $i <= 100; $i++)
                                <option value="{{ $i }}">{{ $i }}%</option>
                            @endfor
                        </x-ui.select>

                        <x-ui.input label="First Name" name="firstName" wire:model.blur="firstName" placeholder="Patient first name" required />
                        <x-ui.input label="Last Name" name="lastName" wire:model.blur="lastName" placeholder="Patient last name" required />

                        <x-ui.select label="Gender" name="gender" wire:model.live="gender" required>
                            <option value="">Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </x-ui.select>

                        <x-ui.input label="Date of Birth" name="dateOfBirth" type="date" wire:model.live="dateOfBirth" required />

                        <x-ui.input label="Estimated Age" value="{{ $estimatedAge !== null ? $estimatedAge . ' years' : 'Select date of birth' }}" disabled />
                        <x-ui.input label="Occupation" name="occupation" wire:model.blur="occupation" placeholder="Patient occupation" />

                        <x-ui.select label="State of Origin" name="stateId" wire:model.live="stateId">
                            <option value="">Select state</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select label="Local Government" name="lga" wire:model.live="lga" :disabled="$stateId === ''">
                            <option value="">Select LGA</option>
                            @foreach($lgas as $localGovernment)
                                <option value="{{ $localGovernment->id }}">{{ $localGovernment->name }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select label="Marital Status" name="maritalStatus" wire:model.live="maritalStatus">
                            <option value="">Select status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                        </x-ui.select>

                        <x-ui.input label="Phone Number" name="phoneNumber" type="tel" wire:model.live.debounce.500ms="phoneNumber" required />
                        <x-ui.input label="Email Address" name="email" type="email" wire:model.blur="email" placeholder="patient@example.com" />

                        <div class="md:col-span-2">
                            <x-ui.textarea label="Address" name="address" rows="3" wire:model.blur="address" placeholder="Full address"></x-ui.textarea>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Next Of Kin Information">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input label="Name" name="nokName" wire:model.blur="nokName" required />
                        <x-ui.select label="Relationship" name="nokRelationship" wire:model.live="nokRelationship" required>
                            <option value="">Select relationship</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Husband">Husband</option>
                            <option value="Wife">Wife</option>
                            <option value="Brother">Brother</option>
                            <option value="Sister">Sister</option>
                            <option value="Son">Son</option>
                            <option value="Daughter">Daughter</option>
                            <option value="Guardian">Guardian</option>
                        </x-ui.select>
                        <x-ui.input label="Telephone" name="nokTelephone" type="tel" wire:model.blur="nokTelephone" required />
                        <x-ui.input label="Contact Address" name="nokContactAddress" wire:model.blur="nokContactAddress" />
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6 xl:col-span-4">
                <x-ui.card title="Patient Flags">
                    <div class="space-y-3">
                        <label class="flex items-center justify-between gap-4 rounded-md border border-med-line bg-white px-4 py-3 text-sm font-medium text-med-muted">
                            <span>ANC Patient</span>
                            <input class="h-4 w-4 rounded border-med-line text-med-primary" type="checkbox" wire:model.live="anc">
                        </label>
                        <label class="flex items-center justify-between gap-4 rounded-md border border-med-line bg-white px-4 py-3 text-sm font-medium text-med-muted">
                            <span>Walk-in File</span>
                            <input class="h-4 w-4 rounded border-med-line text-med-primary" type="checkbox" wire:model.live="isWalkIn">
                        </label>
                    </div>
                </x-ui.card>

                <x-ui.card title="Billing Preview">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-med-muted">File Type</span>
                            <span class="font-semibold text-med-ink">{{ $selectedFileType->name ?? 'Not selected' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-med-muted">Base Fee</span>
                            <span class="font-semibold text-med-ink">NGN {{ number_format($selectedFileType->price ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-med-muted">Discount</span>
                            <span class="font-semibold text-med-ink">{{ $discount }}%</span>
                        </div>
                        <div class="border-t border-med-line pt-3">
                            <div class="flex justify-between gap-4">
                                <span class="text-med-muted">Consultation</span>
                                <span class="font-semibold text-med-ink">NGN {{ number_format($anc ? 500 : 1000, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <div class="sticky top-24 space-y-3">
                    <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">
                            <i class="bi bi-check-circle"></i>
                            Register Patient
                        </span>
                        <span wire:loading wire:target="save">Registering...</span>
                    </x-ui.button>

                    <a href="{{ route('record.patients.index') }}">
                        <x-ui.button type="button" variant="secondary" class="w-full">Cancel</x-ui.button>
                    </a>
                </div>
            </div>
        </div>
    </form>
</x-ui.page>


