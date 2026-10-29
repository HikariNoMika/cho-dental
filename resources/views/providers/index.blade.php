<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dentists / Providers</h2>
            <a href="{{ route('providers.create') }}" class="underline">Add Provider</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-600">{{ session('status') }}</div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left">Name</th>
                                    <th class="px-4 py-2 text-left">PAN</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($providers as $provider)
                                    <tr>
                                        <td class="px-4 py-2">{{ $provider->displayName() }}</td>
                                        <td class="px-4 py-2">{{ $provider->pan }}</td>
                                        <td class="px-4 py-2 text-right">
                                            <a class="underline" href="{{ route('providers.show', $provider) }}">View</a>
                                            <a class="underline ml-2" href="{{ route('providers.edit', $provider) }}">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $providers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
