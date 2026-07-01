@php
    /** @var array{rows: \Illuminate\Support\Collection<int, array{entry: \App\Models\Entry, own: \App\Models\Scoresheet, own_total: float, other_avg_total: float, other_count: int, delta: float, is_outlier: bool, sections: array<string, array{own: float, avg: float}>}>, entries_judged: int, not_flagged_count: int, largest_gap: array{entry: \App\Models\Entry, delta: float}|null} $comparison */
    $outliers = $comparison['rows']->filter(fn (array $row): bool => $row['is_outlier']);
@endphp
<x-page :title="'Judge Report — ' . $competition->name . ' — ' . $judge->name" :vite="['resources/css/app.css', 'resources/js/app.js']">
    <x-slot:head>
        <style>
            @media print {
                .no-print { display: none !important; }
                body { background: white; color: black; }
            }
        </style>
    </x-slot:head>
<body class="bg-white dark:bg-gray-950 text-gray-900 dark:text-white font-sans p-6 max-w-5xl mx-auto">

    {{-- Print button --}}
    <div class="no-print mb-6 flex gap-4 items-center">
        <button onclick="window.print()" class="bg-amber-600 text-white px-4 py-2 rounded text-sm">Print</button>
        <a href="{{ route('results.index', $competition) }}" class="text-amber-700 dark:text-amber-400 underline text-sm self-center">← All results</a>
        <div class="ml-auto">
            <x-theme-toggle />
        </div>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ $competition->name }} — Judge Report</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $judge->name }}
            @if($competition->judging_date)
                · {{ $competition->judging_date->format('F j, Y') }}
            @endif
        </p>
    </div>

    @if($comparison['entries_judged'] > 0)
    {{-- Judging profile panel --}}
    <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5 mb-10">
        <div class="flex items-baseline justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Your Judging Profile</h2>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $comparison['entries_judged'] }} {{ Str::plural('entry', $comparison['entries_judged']) }} judged</span>
        </div>
        <div class="grid grid-cols-2 gap-6 mb-4">
            <div>
                @if($comparison['largest_gap'])
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">largest gap · #{{ $comparison['largest_gap']['entry']->bottle_number }} {{ $comparison['largest_gap']['entry']->style?->name }} ({{ $comparison['largest_gap']['entry']->style?->code }})</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ $comparison['largest_gap']['delta'] > 0 ? '+' : '' }}{{ number_format($comparison['largest_gap']['delta'], 1) }}</div>
                @endif
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">entries not flagged as outliers</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ $comparison['not_flagged_count'] }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">of {{ $comparison['entries_judged'] }}</span></div>
            </div>
        </div>
        <p class="text-sm text-gray-700 dark:text-gray-300 border-t border-gray-200 dark:border-gray-700 pt-3">
            @if($outliers->isEmpty())
                Your scores were consistent with the rest of the panel across all entries.
            @else
                You're mostly in line with the rest of the panel.
                {{ $outliers->count() === 1 ? 'One entry stood out' : $outliers->count() . ' entries stood out' }}:
                @foreach($outliers as $row)
                    <strong>#{{ $row['entry']->bottle_number }} {{ $row['entry']->style?->name }}</strong>
                    ({{ $row['delta'] > 0 ? '+' : '' }}{{ number_format($row['delta'], 1) }}, you scored {{ $row['delta'] > 0 ? 'higher' : 'lower' }}){{ ! $loop->last ? ' and ' : '' }}
                @endforeach
                — worth a look below.
            @endif
        </p>
    </div>

    {{-- Delta table --}}
    <section class="mb-10">
        <h2 class="text-lg font-bold border-b border-gray-300 dark:border-gray-700 pb-1 mb-2">Your Scores vs. the Field</h2>
        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 mb-3">
            <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded bg-amber-400"></span> Your score</span>
            <span class="flex items-center gap-1.5"><span class="inline-block w-0.5 h-3 bg-gray-700 dark:bg-gray-300"></span> Other judges' average</span>
        </div>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <th class="text-left py-1.5 px-2 font-medium">Entry</th>
                    <th class="text-center py-1.5 px-2 font-medium">Your Total</th>
                    <th class="text-center py-1.5 px-2 font-medium">Others' Avg</th>
                    <th class="text-center py-1.5 px-2 font-medium">Δ</th>
                    <th class="text-center py-1.5 px-2 font-medium w-10"></th>
                </tr>
            </thead>
            @foreach($comparison['rows'] as $row)
            <tbody x-data="{ open: false }">
                <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer" @click="open = !open">
                    <td class="py-1.5 px-2">
                        <div class="font-medium">
                            #{{ $row['entry']->bottle_number }} · {{ $row['entry']->style?->name }}
                            @if($row['is_outlier'])
                                <span class="text-xs font-medium {{ $row['delta'] > 0 ? 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/40' : 'text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/40' }} px-1.5 py-0.5 rounded ml-1">outlier</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['entry']->style?->code }}</div>
                    </td>
                    <td class="text-center py-1.5 px-2 font-semibold">{{ number_format($row['own_total'], 1) }}</td>
                    <td class="text-center py-1.5 px-2 text-gray-500 dark:text-gray-400">{{ $row['other_count'] > 0 ? number_format($row['other_avg_total'], 1) : '—' }}</td>
                    <td class="text-center py-1.5 px-2 font-medium text-gray-500 dark:text-gray-400">{{ $row['other_count'] > 0 ? ($row['delta'] > 0 ? '+' : '') . number_format($row['delta'], 1) : '—' }}</td>
                    <td class="text-center py-1.5 px-2 text-gray-400" x-text="open ? '▴' : '▾'">▾</td>
                </tr>
                <tr x-show="open" x-cloak class="bg-gray-50/70 dark:bg-gray-900/50">
                    <td colspan="5" class="px-4 py-3">
                        @if($row['other_count'] === 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400">No other judges have submitted a scoresheet for this entry yet.</p>
                        @else
                        <div class="space-y-1.5 max-w-2xl">
                            @foreach($row['sections'] as $section => $values)
                            @php $max = \App\Models\Scoresheet::SECTION_MAX[$section]; @endphp
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-20 text-gray-500 dark:text-gray-400">{{ ucfirst($section) }} /{{ $max }}</span>
                                <div class="relative flex-1 h-3 bg-gray-200 dark:bg-gray-700 rounded">
                                    <div class="absolute inset-y-0 left-0 bg-amber-400 rounded" style="width: {{ min(100, $values['own'] / $max * 100) }}%"></div>
                                    <div class="absolute inset-y-0 w-0.5 bg-gray-700 dark:bg-gray-300" style="left: {{ min(100, $values['avg'] / $max * 100) }}%"></div>
                                </div>
                                <span class="w-32 text-right text-gray-600 dark:text-gray-400"><span class="font-semibold text-gray-900 dark:text-white">You {{ rtrim(rtrim(number_format($values['own'], 1), '0'), '.') }}</span> · Avg {{ number_format($values['avg'], 1) }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </td>
                </tr>
            </tbody>
            @endforeach
        </table>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Click a row to expand the per-section comparison. Outlier rows (more than 7 points from the other judges' average) are labelled.</p>
    </section>
    @else
    <p class="text-sm text-gray-500 dark:text-gray-400 italic mb-10">No submitted scoresheets yet.</p>
    @endif

    {{-- Full standings --}}
    <section>
        <h2 class="text-lg font-bold border-b border-gray-300 dark:border-gray-700 pb-1 mb-4">Full Standings</h2>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">
                    <th class="text-left py-2 px-3 font-medium">#</th>
                    <th class="text-left py-2 px-3 font-medium">Brewer</th>
                    <th class="text-left py-2 px-3 font-medium">Style</th>
                    <th class="text-center py-2 px-3 font-medium">Score</th>
                    <th class="text-center py-2 px-3 font-medium">Band</th>
                    <th class="text-center py-2 px-3 font-medium">Sheets</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rankedEntries as $row)
                @php
                    /** @var array{entry: \App\Models\Entry, sheets: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scoresheet>, avg_total: float, avg_overall: float, sheet_count: int, band: string, band_key: string, placement: int} $row */
                @endphp
                <tr class="border-t border-gray-100 dark:border-gray-800">
                    <td class="py-2 px-3 font-bold text-gray-900 dark:text-white">{{ $row['placement'] }}</td>
                    <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $row['entry']->member?->name }}</td>
                    <td class="py-2 px-3 text-gray-600 dark:text-gray-400">{{ $row['entry']->style?->code }}@if($row['entry']->abv)<span class="text-xs text-gray-400 dark:text-gray-500 ml-1">{{ $row['entry']->abv }}% ABV</span>@endif</td>
                    <td class="text-center py-2 px-3 font-semibold">{{ number_format($row['avg_total'], 1) }}</td>
                    <td class="text-center py-2 px-3">
                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ \App\Services\CompetitionRankingService::bandColor($row['band_key']) }}">
                            {{ $row['band'] }}
                        </span>
                    </td>
                    <td class="text-center py-2 px-3 text-gray-600 dark:text-gray-400">{{ $row['sheet_count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </section>

</body>
</x-page>
