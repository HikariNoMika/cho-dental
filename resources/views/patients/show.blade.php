<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Patient Details</h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('patients.visits.create', $patient) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Add Visit
                </a>
                @if ($patient->visits->isNotEmpty())
                    <a href="{{ route('claims.forms', $patient->visits->first()) }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Claim Forms
                    </a>
                @endif
                <a href="{{ route('patients.edit', $patient) }}" class="underline">Edit</a>
                <a href="{{ route('patients.index') }}" class="underline">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2">
                    <div><span class="font-semibold">Name:</span> {{ $patient->fullName() }}</div>
                    <div><span class="font-semibold">DOB:</span> {{ $patient->dob?->format('Y-m-d') }} ({{ $patient->age }})</div>
                    <div><span class="font-semibold">Sex:</span> {{ $patient->sex }}</div>
                    <div><span class="font-semibold">PhilHealth PIN:</span> {{ $patient->philhealthPin() }}</div>
                    <div><span class="font-semibold">Type:</span> {{ $patient->is_member ? 'Member' : 'Dependent' }}</div>
                    @php
                        $memberProfile = ! $patient->is_member ? $patient->memberProfile() : null;
                    @endphp
                    @if ($memberProfile)
                        <div><span class="font-semibold">Member:</span> {{ $memberProfile->fullName() }}</div>
                        <div><span class="font-semibold">Member PIN:</span> {{ $memberProfile->philhealthPin() ?? '—' }}</div>
                    @endif
                    <div><span class="font-semibold">Relationship:</span> {{ $patient->relationship }}</div>
                    <div><span class="font-semibold">Contact Number:</span> {{ $patient->contact_number ?? '—' }}</div>
                    <div><span class="font-semibold">Claim Form Signature:</span> {{ $patient->claim_signatory === 'guardian' && filled($patient->guardian_name) ? $patient->guardian_name : $patient->fullName() }}</div>
                    @if (filled($patient->guardian_name))
                        <div>
                            <span class="font-semibold">Guardian:</span>
                            {{ $patient->guardian_name }} ({{ $patient->guardian_relationship }})
                            @if (filled($patient->guardian_contact_number))
                                — {{ $patient->guardian_contact_number }}
                            @endif
                        </div>
                    @endif
                    <div>
                        <span class="font-semibold">Address:</span>
                        @php
                            $addressParts = array_filter([
                                $patient->barangay,
                                $patient->city,
                                $patient->province,
                                $patient->region,
                            ], fn ($part) => filled($part));
                        @endphp
                        {{ $addressParts ? implode(', ', $addressParts) : '—' }}
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-semibold text-lg mb-4">Visits</h3>

                    @php
                        $year = now()->year;
                        $yearVisits = $patient->visits
                            ->filter(fn ($visit) => $visit->service_date?->year === $year)
                            ->sortBy([['service_date', 'asc'], ['id', 'asc']])
                            ->values();
                        $yearVisitCount = $yearVisits->count();
                        $firstYearVisit = $yearVisits->first();
                        $nextVisitNumber = match ($yearVisitCount) {
                            0 => 1,
                            1 => 2,
                            default => null,
                        };
                    @endphp

                    <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="font-semibold text-gray-900">{{ $year }} services</div>
                        @if ($yearVisitCount === 0)
                            <p class="text-sm text-gray-600">No service recorded this year. Next service: 1st visit (OPH01 series).</p>
                        @else
                            <p class="text-sm text-gray-600">
                                {{ $yearVisitCount }} of 2 yearly services recorded.
                                First visit: {{ $firstYearVisit->service_date?->format('M d, Y') }}
                                @if ($firstYearVisit->claim_code)
                                    ({{ $firstYearVisit->claim_code }})
                                @endif
                            </p>
                            @if ($nextVisitNumber === 2)
                                <p class="text-sm text-amber-600">Next service: 2nd visit (OPH02 series).</p>
                            @elseif ($nextVisitNumber === null)
                                <p class="text-sm text-amber-600">Both yearly services are already recorded. Double-check before adding another.</p>
                            @endif
                        @endif
                    </div>

                    @if ($patient->visits->isEmpty())
                        <p class="text-gray-600">No visits recorded for this patient.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left">Service Date</th>
                                        <th class="px-4 py-2 text-left">Claim Code</th>
                                        <th class="px-4 py-2 text-left">Dentist</th>
                                        <th class="px-4 py-2 text-left">Referral Code</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($patient->visits as $visit)
                                        <tr>
                                            <td class="px-4 py-2">{{ $visit->service_date?->format('Y-m-d') }}</td>
                                            <td class="px-4 py-2">{{ $visit->claim_code ?? '—' }}</td>
                                            <td class="px-4 py-2">{{ $visit->provider?->displayName() }}</td>
                                            <td class="px-4 py-2">{{ $visit->referral_code ?? '—' }}</td>
                                            <td class="px-4 py-2 text-right">
                                                <a href="{{ route('visits.edit', $visit) }}"
                                                   class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300">Edit</a>
                                                <a href="{{ route('claims.forms', $visit) }}"
                                                   class="ml-3 inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                    Claim Forms
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
