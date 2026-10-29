<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Patient</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('patients.update', $patient) }}" class="space-y-4" x-data="{
                        dob: @js(old('dob', $patient->dob?->format('Y-m-d'))),
                        signatory: @js(old('claim_signatory', $patient->claim_signatory)),
                        member: @js((bool) old('is_member', $patient->is_member)),
                        maxMinorAge: {{ (int) config('patients.minor_max_age', 13) }},
                        get age() {
                            if (! this.dob) return null;
                            const birth = new Date(this.dob + 'T00:00:00');
                            if (Number.isNaN(birth.getTime())) return null;
                            const today = new Date();
                            let age = today.getFullYear() - birth.getFullYear();
                            const month = today.getMonth() - birth.getMonth();
                            if (month < 0 || (month === 0 && today.getDate() < birth.getDate())) age--;
                            return age;
                        },
                        get isMinor() {
                            return this.age !== null && this.age >= 0 && this.age <= this.maxMinorAge;
                        },
                        init() {
                            if (! this.isMinor) {
                                this.signatory = 'patient';
                            } else if (! this.signatory) {
                                this.signatory = 'guardian';
                            }
                            this.$watch('isMinor', (minor) => {
                                this.signatory = minor ? 'guardian' : 'patient';
                            });
                        }
                    }">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="first_name" value="First Name" />
                                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name', $patient->first_name)" required />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="middle_name" value="Middle Name" />
                                <x-text-input id="middle_name" class="block mt-1 w-full" type="text" name="middle_name" :value="old('middle_name', $patient->middle_name)" />
                                <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="last_name" value="Last Name" />
                                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name', $patient->last_name)" required />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="name_ext" value="Extension" />
                                <x-text-input id="name_ext" class="block mt-1 w-full" type="text" name="name_ext" :value="old('name_ext', $patient->name_ext)" />
                                <x-input-error :messages="$errors->get('name_ext')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="dob" value="Date of Birth" />
                                <x-text-input id="dob" class="block mt-1 w-full" type="date" name="dob" x-model="dob" :value="old('dob', $patient->dob?->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('dob')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="sex" value="Sex" />
                                <select id="sex" name="sex" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="MALE" @selected(old('sex', $patient->sex) === 'MALE')>MALE</option>
                                    <option value="FEMALE" @selected(old('sex', $patient->sex) === 'FEMALE')>FEMALE</option>
                                </select>
                                <x-input-error :messages="$errors->get('sex')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="philhealth_pin" value="PhilHealth PIN" />
                                <x-text-input id="philhealth_pin" class="block mt-1 w-full" type="text" name="philhealth_pin" :value="old('philhealth_pin', preg_replace('/\D+/', '', (string) $patient->philhealth_pin))" maxlength="12" placeholder="123456789012" />
                                <p class="mt-1 text-xs text-gray-500">Format: 12-345678901-2</p>
                                <x-input-error :messages="$errors->get('philhealth_pin')" class="mt-2" />
                            </div>
                            <div class="flex items-center mt-6">
                                <input id="is_member" type="checkbox" name="is_member" value="1" x-model="member" @checked(old('is_member', $patient->is_member)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                <label for="is_member" class="ms-2 text-sm text-gray-600">Member</label>
                            </div>
                            <div>
                                <x-input-label for="relationship" value="Relationship" />
                                <x-text-input id="relationship" class="block mt-1 w-full" type="text" name="relationship" :value="old('relationship', $patient->relationship)" />
                                <x-input-error :messages="$errors->get('relationship')" class="mt-2" />
                            </div>
                            @include('patients.partials.address-fields', ['patient' => $patient])
                        </div>

                        @include('patients.partials.member-fields', ['patient' => $patient])
                        @include('patients.partials.guardian-fields', ['patient' => $patient])

                        <div class="flex items-center gap-4">
                            <x-primary-button>Update</x-primary-button>
                            <a href="{{ route('patients.index') }}" class="underline">Cancel</a>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('patients.destroy', $patient) }}" class="mt-6">
                        @csrf
                        @method('DELETE')
                        <x-danger-button onclick="return confirm('Delete this patient?');">Delete</x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
