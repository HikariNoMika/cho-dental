<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Details</h2>
            <div class="flex gap-4">
                <a href="{{ route('users.edit', $user) }}" class="underline">Edit</a>
                <a href="{{ route('users.index') }}" class="underline">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2">
                    <div><span class="font-semibold">Name:</span> {{ $user->fullName() }}</div>
                    <div><span class="font-semibold">Designation:</span> {{ $user->designation }}</div>
                    <div><span class="font-semibold">Email:</span> {{ $user->email }}</div>
                    <div><span class="font-semibold">Verified:</span> {{ $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i') : 'No' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
