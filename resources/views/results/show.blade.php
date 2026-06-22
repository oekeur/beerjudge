@php
    $member = (isset($member) && $member instanceof \App\Models\Member) ? $member : new \App\Models\Member();
    $memberName = $member->name;
    $memberId = $member->id;
@endphp
<x-page :title="'Results — ' . $competition->name . ' — ' . $memberName">
    <x-slot:head>
        <style>
            @media print {
                .no-print { display: none !important; }
                body { background: white; color: black; }
                .page-break { page-break-after: always; }
            }
        </style>
    </x-slot:head>
<body class="bg-white dark:bg-gray-950 text-gray-900 dark:text-white font-sans p-6 max-w-4xl mx-auto">

    {{-- Print button --}}
    <div class="no-print mb-6 flex gap-4 items-center">
        <button onclick="window.print()" class="bg-amber-600 text-white px-4 py-2 rounded text-sm">Print</button>
        <a href="{{ route('results.index', $competition) }}" class="text-amber-700 dark:text-amber-400 underline text-sm self-center">← All results</a>
        <div class="ml-auto">
            <x-theme-toggle />
        </div>
    </div>

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold">{{ $competition->name }} — Results</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $memberName }}
            @if($competition->judging_date)
                · {{ $competition->judging_date->format('F j, Y') }}
            @endif
        </p>
    </div>

    {{-- Part 1: Member's own entries --}}
    @if($memberEntries->isNotEmpty())
    <section class="mb-10">
        <h2 class="text-lg font-bold border-b border-gray-300 dark:border-gray-700 pb-1 mb-4">Your Entries</h2>

        @foreach($memberEntries as $row)
        @php
            /** @var array{entry: \App\Models\Entry, sheets: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scoresheet>, avg_total: float, avg_overall: float, sheet_count: int, band: string, placement: int} $row */
        @endphp
        <div class="mb-8">
            <div class="flex justify-between items-baseline mb-2">
                <h3 class="text-base font-semibold">
                    {{ $row['entry']->style?->code }} · {{ $row['entry']->style?->name }}
                </h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">#{{ $row['entry']->bottle_number }}</span>
            </div>

            <div class="flex gap-6 mb-3 text-sm">
                <div>
                    <span class="font-bold text-xl">{{ number_format($row['avg_total'], 1) }}</span>
                    <span class="text-gray-500 dark:text-gray-400">/ 50</span>
                    <span class="ml-2 font-medium
                        {{ $row['avg_total'] >= 38 ? 'text-green-700 dark:text-green-400' : ($row['avg_total'] >= 30 ? 'text-teal-700 dark:text-teal-400' : ($row['avg_total'] >= 21 ? 'text-yellow-700 dark:text-yellow-400' : 'text-red-700 dark:text-red-400')) }}">
                        {{ $row['band'] }}
                    </span>
                </div>
                <div class="text-gray-600 dark:text-gray-400">Place: <strong>{{ $row['placement'] }}</strong> of {{ $rankedEntries->count() }}</div>
                <div class="text-gray-600 dark:text-gray-400">{{ $row['sheet_count'] }} {{ Str::plural('judge', $row['sheet_count']) }}</div>
            </div>

            @if($row['sheets']->isNotEmpty())
            <table class="w-full text-sm border-collapse mb-2">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <th class="text-left py-1 px-2 font-medium">Judge</th>
                        <th class="text-center py-1 px-2 font-medium">Aroma<br><span class="text-xs font-normal text-gray-500">/12</span></th>
                        <th class="text-center py-1 px-2 font-medium">App.<br><span class="text-xs font-normal text-gray-500">/3</span></th>
                        <th class="text-center py-1 px-2 font-medium">Flavour<br><span class="text-xs font-normal text-gray-500">/20</span></th>
                        <th class="text-center py-1 px-2 font-medium">Mouth.<br><span class="text-xs font-normal text-gray-500">/5</span></th>
                        <th class="text-center py-1 px-2 font-medium">Overall<br><span class="text-xs font-normal text-gray-500">/10</span></th>
                        <th class="text-center py-1 px-2 font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($row['sheets'] as $sheet)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="py-1 px-2 text-gray-700 dark:text-gray-300">{{ $sheet->judge?->name }}</td>
                        <td class="text-center py-1 px-2">{{ $sheet->aroma }}</td>
                        <td class="text-center py-1 px-2">{{ $sheet->appearance }}</td>
                        <td class="text-center py-1 px-2">{{ $sheet->flavour }}</td>
                        <td class="text-center py-1 px-2">{{ $sheet->mouthfeel }}</td>
                        <td class="text-center py-1 px-2">{{ $sheet->overall }}</td>
                        <td class="text-center py-1 px-2 font-semibold">{{ $sheet->total }}</td>
                    </tr>
                    @if($sheet->aroma_notes || $sheet->appearance_notes || $sheet->flavour_notes || $sheet->mouthfeel_notes || $sheet->overall_notes)
                    <tr class="border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
                        <td colspan="7" class="py-2 px-3 text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            @if($sheet->aroma_notes)<div><strong>Aroma:</strong> {{ $sheet->aroma_notes }}</div>@endif
                            @if($sheet->appearance_notes)<div><strong>Appearance:</strong> {{ $sheet->appearance_notes }}</div>@endif
                            @if($sheet->flavour_notes)<div><strong>Flavour:</strong> {{ $sheet->flavour_notes }}</div>@endif
                            @if($sheet->mouthfeel_notes)<div><strong>Mouthfeel:</strong> {{ $sheet->mouthfeel_notes }}</div>@endif
                            @if($sheet->overall_notes)<div><strong>Overall:</strong> {{ $sheet->overall_notes }}</div>@endif
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No submitted scoresheets.</p>
            @endif
        </div>
        @endforeach
    </section>
    @endif

    {{-- Part 2: Full standings --}}
    <section>
        <h2 class="text-lg font-bold border-b border-gray-300 dark:border-gray-700 pb-1 mb-4">Full Standings</h2>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <th class="text-left py-1 px-2 font-medium">#</th>
                    <th class="text-left py-1 px-2 font-medium">Brewer</th>
                    <th class="text-left py-1 px-2 font-medium">Style</th>
                    <th class="text-center py-1 px-2 font-medium">Score</th>
                    <th class="text-center py-1 px-2 font-medium">Band</th>
                    <th class="text-center py-1 px-2 font-medium">Sheets</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rankedEntries as $row)
                @php
                    /** @var array{entry: \App\Models\Entry, sheets: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scoresheet>, avg_total: float, avg_overall: float, sheet_count: int, band: string, placement: int} $row */
                @endphp
                <tr class="border-t border-gray-200 dark:border-gray-700 {{ $row['entry']->member_id === $memberId ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}">
                    <td class="py-1 px-2 font-bold text-gray-900 dark:text-white">{{ $row['placement'] }}</td>
                    <td class="py-1 px-2 text-gray-700 dark:text-gray-300">{{ $row['entry']->member?->name }}</td>
                    <td class="py-1 px-2 text-gray-600 dark:text-gray-400">{{ $row['entry']->style?->code }}</td>
                    <td class="text-center py-1 px-2 font-semibold">{{ number_format($row['avg_total'], 1) }}</td>
                    <td class="text-center py-1 px-2 text-xs font-medium
                        {{ $row['avg_total'] >= 38 ? 'text-green-700 dark:text-green-400' : ($row['avg_total'] >= 30 ? 'text-teal-700 dark:text-teal-400' : ($row['avg_total'] >= 21 ? 'text-yellow-700 dark:text-yellow-400' : 'text-red-700 dark:text-red-400')) }}">
                        {{ $row['band'] }}
                    </td>
                    <td class="text-center py-1 px-2 text-gray-600 dark:text-gray-400">{{ $row['sheet_count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </section>

</body>
</x-page>
