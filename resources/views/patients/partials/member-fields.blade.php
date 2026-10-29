@php
    $patient = $patient ?? null;
    $linkedMember = $patient?->member;

    $memberFirstName = old('member_first_name', filled($patient?->member_first_name) ? $patient->member_first_name : ($linkedMember?->first_name ?? ''));
    $memberMiddleName = old('member_middle_name', filled($patient?->member_middle_name) ? $patient->member_middle_name : ($linkedMember?->middle_name ?? ''));
    $memberLastName = old('member_last_name', filled($patient?->member_last_name) ? $patient->member_last_name : ($linkedMember?->last_name ?? ''));
    $memberNameExt = old('member_name_ext', filled($patient?->member_name_ext) ? $patient->member_name_ext : ($linkedMember?->name_ext ?? ''));
    $memberPin = old('member_pin', filled($patient?->member_pin) ? preg_replace('/\D+/', '', (string) $patient->member_pin) : preg_replace('/\D+/', '', (string) ($linkedMember?->philhealthPin() ?? '')));
@endphp

<div x-show="!member" x-cloak class="mt-4 border-t border-gray-200 pt-4">
    <h3 class="font-semibold text-gray-900">Member / Principal (if dependent)</h3>
    <p class="text-sm text-gray-500">
        Enter the member's name and PIN separately. These details are saved with the dependent
        even when the member is not registered as a patient. A matching name or PIN links the
        member record automatically.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
            <x-input-label for="member_first_name" value="Member First Name" />
            <x-text-input id="member_first_name" class="block mt-1 w-full" type="text" name="member_first_name" :value="$memberFirstName" />
            <x-input-error :messages="$errors->get('member_first_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="member_middle_name" value="Member Middle Name" />
            <x-text-input id="member_middle_name" class="block mt-1 w-full" type="text" name="member_middle_name" :value="$memberMiddleName" />
            <x-input-error :messages="$errors->get('member_middle_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="member_last_name" value="Member Last Name" />
            <x-text-input id="member_last_name" class="block mt-1 w-full" type="text" name="member_last_name" :value="$memberLastName" />
            <x-input-error :messages="$errors->get('member_last_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="member_name_ext" value="Member Extension" />
            <x-text-input id="member_name_ext" class="block mt-1 w-full" type="text" name="member_name_ext" :value="$memberNameExt" />
            <x-input-error :messages="$errors->get('member_name_ext')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="member_pin" value="Member PIN" />
            <x-text-input id="member_pin" class="block mt-1 w-full" type="text" name="member_pin" :value="$memberPin" maxlength="12" placeholder="123456789012" />
            <p class="mt-1 text-xs text-gray-500">Separate from the member's name. Format: 12-345678901-2</p>
            <x-input-error :messages="$errors->get('member_pin')" class="mt-2" />
        </div>
    </div>
</div>
