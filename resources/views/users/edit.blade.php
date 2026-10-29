<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit User</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="first_name" value="First Name" />
                                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name', $user->first_name)" required />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="last_name" value="Last Name" />
                                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name', $user->last_name)" required />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="designation" value="Designation" />
                                <x-text-input id="designation" class="block mt-1 w-full" type="text" name="designation" :value="old('designation', $user->designation)" placeholder="e.g., Encoder, Clerk" />
                                <x-input-error :messages="$errors->get('designation')" class="mt-2" />
                            </div>
                        </div>
                        <input type="hidden" name="name" value="{{ old('name', $user->name) }}">
                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password" value="New Password (leave blank to keep current)" />
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" value="Confirm New Password" />
                            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" />
                        </div>
                        <div class="flex items-center gap-4">
                            <x-primary-button>Update</x-primary-button>
                            <a href="{{ route('users.index') }}" class="underline">Cancel</a>
                        </div>
                    </form>
                    @if (auth()->id() !== $user->id)
                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-6">
                            @csrf
                            @method('DELETE')
                            <x-danger-button onclick="return confirm('Delete this user?');">Delete</x-danger-button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
