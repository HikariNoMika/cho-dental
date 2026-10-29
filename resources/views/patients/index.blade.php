<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Patients</h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Patient Records</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Newest encoded patients appear first.</p>
            </div>
            <a href="{{ route('patients.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Patient
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <form method="GET" action="{{ route('patients.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
                    <div class="flex-1">
                        <x-input-label for="q" value="Search patients" />
                        <x-text-input id="q" name="q" type="search" :value="$search" placeholder="Name, PIN, contact number, or barangay" class="block mt-1 w-full" />
                    </div>
                    <div class="flex items-center gap-3">
                        <x-primary-button>Search</x-primary-button>
                        @if ($search !== '')
                            <a href="{{ route('patients.index') }}" class="underline text-sm">Clear</a>
                        @endif
                    </div>
                </form>

                @if ($search !== '')
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $patients->total() }} result(s) for "{{ $search }}".
                    </p>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">DOB</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sex</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PIN</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $year }} Visits</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($patients as $patient)
                                @php
                                    $yearVisits = $patient->visitsThisYear;
                                    $yearVisitCount = $yearVisits->count();
                                    $firstYearVisit = $yearVisits->first();
                                    $latestYearVisit = $yearVisits->last();
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center mr-3">
                                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ strtoupper(substr($patient->first_name, 0, 1)) }}</span>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $patient->fullName() }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $patient->dob?->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $patient->sex }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $patient->philhealthPin() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $patient->contact_number ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $patient->is_member ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400' }}">
                                            {{ $patient->is_member ? 'Member' : 'Dependent' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($yearVisitCount === 0)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                No visit
                                            </span>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Next: 1st visit</div>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $yearVisitCount >= 2 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400' }}">
                                                {{ $yearVisitCount === 1 ? '1st visit' : ($yearVisitCount === 2 ? '2nd visit' : $yearVisitCount.' visits') }}
                                            </span>
                                            @if ($firstYearVisit)
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    First: {{ $firstYearVisit->service_date?->format('M d, Y') }}
                                                </div>
                                            @endif
                                            @if ($latestYearVisit?->claim_code)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $latestYearVisit->claim_code }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <a href="{{ route('patients.visits.create', $patient) }}" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300">Add Visit</a>
                                        @if ($patient->latestVisit)
                                            <a href="{{ route('claims.forms', $patient->latestVisit) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Forms</a>
                                        @endif
                                        <a href="{{ route('patients.show', $patient) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">View</a>
                                        <a href="{{ route('patients.edit', $patient) }}" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        @if ($search !== '')
                                            No patients matched "{{ $search }}".
                                        @else
                                            No patients yet.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">
                    {{ $patients->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
