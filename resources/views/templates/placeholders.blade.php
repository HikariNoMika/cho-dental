<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Template Placeholders</h2>
            <a href="{{ route('templates.index') }}" class="underline">Back to Templates</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 prose dark:prose-invert max-w-none">
                    {!! \Illuminate\Support\Str::markdown($markdown) !!}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
