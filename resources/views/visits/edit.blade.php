<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Edit Visit</h2>
            <a href="{{ route('patients.show', $patient) }}" class="underline">Back to Patient</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-2">
                    <h3 class="font-semibold text-lg">{{ $patient->fullName() }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        PhilHealth PIN: {{ $patient->philhealthPin() ?? '—' }}
                        · {{ $patient->is_member ? 'Member' : 'Dependent' }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Referral code: {{ $visit->referral_code ?? 'Generated when a form is first downloaded' }}
                    </p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('visits.update', $visit) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="service_date" value="Service Date" />
                                <x-text-input id="service_date" class="block mt-1 w-full" type="date" name="service_date" :value="old('service_date', $visit->service_date?->toDateString())" required />
                                <x-input-error :messages="$errors->get('service_date')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="provider_id" value="Dentist" />
                                <select id="provider_id" name="provider_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select</option>
                                    @foreach ($providers as $provider)
                                        <option value="{{ $provider->id }}" @selected((int) old('provider_id', $visit->provider_id) === $provider->id)>
                                            {{ $provider->displayName() }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('provider_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="claim_code" value="Claim Code" />
                                <select id="claim_code" name="claim_code" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    @foreach ($visitCodes as $code)
                                        <option value="{{ $code }}" @selected(old('claim_code', $visit->claim_code) === $code)>
                                            {{ $code }} — {{ \App\Models\Visit::visitNumberForCode($code) === 2 ? '2nd service' : '1st service' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    OPH01 series for the first service this year, OPH02 series for the second.
                                </p>
                                <x-input-error :messages="$errors->get('claim_code')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="disposition" value="Disposition" />
                                <x-text-input id="disposition" class="block mt-1 w-full" type="text" name="disposition" :value="old('disposition', $visit->disposition)" />
                                <x-input-error :messages="$errors->get('disposition')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="working_dx" value="Working Diagnosis" />
                                <x-text-input id="working_dx" class="block mt-1 w-full" type="text" name="working_dx" :value="old('working_dx', $visit->working_dx)" />
                                <x-input-error :messages="$errors->get('working_dx')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="final_dx" value="Final Diagnosis" />
                                <x-text-input id="final_dx" class="block mt-1 w-full" type="text" name="final_dx" :value="old('final_dx', $visit->final_dx)" />
                                <x-input-error :messages="$errors->get('final_dx')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save Visit</x-primary-button>
                            <a href="{{ route('patients.show', $patient) }}" class="underline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
