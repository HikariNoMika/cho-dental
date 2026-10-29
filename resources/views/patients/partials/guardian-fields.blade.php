@php
    $patient = $patient ?? null;
    $guardianName = old('guardian_name', $patient?->guardian_name ?? '');
    $guardianRelationship = old('guardian_relationship', $patient?->guardian_relationship ?? '');
    $guardianContactNumber = old('guardian_contact_number', $patient?->guardian_contact_number ?? '');
    $minorMaxAge = (int) config('patients.minor_max_age', 13);
    $guardianRelationships = ['MOTHER', 'FATHER', 'GUARDIAN', 'GRANDPARENT', 'OTHER'];
@endphp

<div class="mt-4 border-t border-gray-200 pt-4">
    <h3 class="font-semibold text-gray-900">Claim Form Signature</h3>
    <p class="text-sm text-gray-500">
        Choose whose name will appear on the Claim Form 2 signature line. Guardian details
        are only needed when a guardian signs for a patient aged {{ $minorMaxAge }} and below.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
            <x-input-label for="claim_signatory" value="Who will sign?" />
            <select id="claim_signatory" name="claim_signatory" x-model="signatory" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="patient">Patient</option>
                <option value="guardian" x-bind:disabled="!isMinor">Guardian / Authorized Representative</option>
            </select>
            <p x-show="isMinor" class="mt-1 text-xs text-gray-500">
                Select Guardian if a parent or guardian will sign. Select Patient if there is no guardian.
            </p>
            <p x-show="!isMinor" class="mt-1 text-xs text-gray-500">
                Only the patient can sign for patients above {{ $minorMaxAge }}.
            </p>
            <x-input-error :messages="$errors->get('claim_signatory')" class="mt-2" />
        </div>
    </div>

    <div x-show="isMinor && signatory === 'guardian'" x-cloak class="mt-4">
        <h4 class="font-semibold text-gray-900">Guardian Details</h4>
        <p class="text-sm text-gray-500">
            Required when the guardian will sign the claim form.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="guardian_name" value="Guardian Full Name" />
                <x-text-input id="guardian_name" class="block mt-1 w-full" type="text" name="guardian_name" :value="$guardianName" x-bind:required="signatory === 'guardian'" placeholder="Full name of guardian" />
                <x-input-error :messages="$errors->get('guardian_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="guardian_relationship" value="Relationship to Patient" />
                <select id="guardian_relationship" name="guardian_relationship" x-bind:required="signatory === 'guardian'" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">Select</option>
                    @foreach ($guardianRelationships as $relationship)
                        <option value="{{ $relationship }}" @selected($guardianRelationship === $relationship)>
                            {{ ucfirst(strtolower($relationship)) }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('guardian_relationship')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="guardian_contact_number" value="Guardian Contact Number" />
                <x-text-input id="guardian_contact_number" class="block mt-1 w-full" type="tel" name="guardian_contact_number" :value="$guardianContactNumber" maxlength="30" placeholder="09XX XXX XXXX" />
                <x-input-error :messages="$errors->get('guardian_contact_number')" class="mt-2" />
            </div>
        </div>
    </div>
</div>
