<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dental Aide Details</h2>
            <div class="flex gap-4">
                <a href="{{ route('dental-aides.edit', $dentalAide) }}" class="underline">Edit</a>
                <a href="{{ route('dental-aides.index') }}" class="underline">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2">
                    <div><span class="font-semibold">Name:</span> {{ $dentalAide->fullName }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
