<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Provider Details</h2>
            <div class="flex gap-4">
                <a href="{{ route('providers.edit', $provider) }}" class="underline">Edit</a>
                <a href="{{ route('providers.index') }}" class="underline">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2">
                    <div><span class="font-semibold">Name:</span> {{ $provider->displayName() }}</div>
                    <div><span class="font-semibold">Credentials:</span> {{ $provider->credentials }}</div>
                    <div><span class="font-semibold">PAN:</span> {{ $provider->pan }}</div>
                    <div><span class="font-semibold">Signature Path:</span> {{ $provider->signature_path }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
