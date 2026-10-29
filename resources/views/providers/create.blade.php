<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Dentist / Provider</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('providers.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="first_name" value="First Name" />
                                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="middle_name" value="Middle Name" />
                                <x-text-input id="middle_name" class="block mt-1 w-full" type="text" name="middle_name" :value="old('middle_name')" />
                                <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="last_name" value="Last Name" />
                                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="name_ext" value="Extension" />
                                <x-text-input id="name_ext" class="block mt-1 w-full" type="text" name="name_ext" :value="old('name_ext')" />
                                <x-input-error :messages="$errors->get('name_ext')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="credentials" value="Credentials (e.g., DMD)" />
                                <x-text-input id="credentials" class="block mt-1 w-full" type="text" name="credentials" :value="old('credentials', 'DMD')" />
                                <x-input-error :messages="$errors->get('credentials')" class="mt-2" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="pan" value="PhilHealth Accreditation Number (PAN)" />
                                <x-text-input id="pan" class="block mt-1 w-full" type="text" name="pan" :value="old('pan')" required />
                                <x-input-error :messages="$errors->get('pan')" class="mt-2" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="signature_path" value="Signature Path (optional)" />
                                <x-text-input id="signature_path" class="block mt-1 w-full" type="text" name="signature_path" :value="old('signature_path')" />
                                <x-input-error :messages="$errors->get('signature_path')" class="mt-2" />
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <x-primary-button>Save</x-primary-button>
                            <a href="{{ route('providers.index') }}" class="underline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
