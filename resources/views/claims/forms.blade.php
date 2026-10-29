<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Claim Forms</h2>
            <a href="{{ route('patients.show', $visit->patient) }}" class="underline">Back to Patient</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2">
                    <div><span class="font-semibold">Patient:</span> {{ $visit->patient->fullName() }}</div>
                    <div><span class="font-semibold">Service Date:</span> {{ $visit->service_date?->format('Y-m-d') }}</div>
                    <div>
                        <span class="font-semibold">Claim Code:</span>
                        {{ $visit->claim_code ?? '—' }}
                        @if ($visit->visitNumber())
                            ({{ $visit->visitNumberLabel() }})
                        @endif
                    </div>
                    <div><span class="font-semibold">Dentist:</span> {{ $visit->provider?->displayName() }}</div>
                    <div><span class="font-semibold">Referral Code:</span> {{ $visit->referral_code ?? 'Generated when downloaded' }}</div>
                    <p class="pt-3 text-sm text-gray-600">
                        Use Print for a browser preview, or download the DOCX and print from Microsoft Word for the exact form layout.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['form' => 'referral', 'title' => 'Referral Form', 'description' => 'Referral transaction form'],
                    ['form' => 'claim-1', 'title' => 'Claim Form 1', 'description' => 'Annex E page 1'],
                    ['form' => 'claim-2', 'title' => 'Claim Form 2', 'description' => 'Annex E page 2'],
                ] as $item)
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <span class="block font-semibold text-gray-900">{{ $item['title'] }}</span>
                        <span class="mt-1 block text-sm text-gray-600">{{ $item['description'] }}</span>
                        <div class="mt-4 flex items-center gap-3">
                            <button type="button"
                                    onclick="window.printDocx(@js(route('claims.forms.download', [$visit, $item['form']])))"
                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Print
                            </button>
                            <a href="{{ route('claims.forms.download', [$visit, $item['form']]) }}"
                               class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                Download DOCX
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>
                <a href="{{ route('claims.forms.zip', $visit) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Download All Three Forms (.zip)
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
