@php
    $member = (isset($member) && $member instanceof \App\Models\Member) ? $member : new \App\Models\Member();
    $memberName = $member->name;
    $memberId = $member->id;
@endphp
<x-page :title="'Results — ' . $competition->name . ' — ' . $memberName" :vite="['resources/css/app.css', 'resources/js/app.js']">
    <x-slot:head>
        <style>
            @media print {
                .no-print { display: none !important; }
                body { background: white; color: black; }
                .page-break { page-break-after: always; }
                .notes-row { display: table-row !important; }
                .chevron { display: none !important; }
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
            /** @var array{entry: \App\Models\Entry, sheets: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scoresheet>, avg_total: float, avg_overall: float, sheet_count: int, band: string, band_key: string, placement: int} $row */
            $ordinal = match ($row['placement'] % 10) {
                1 => $row['placement'] % 100 === 11 ? 'th' : 'st',
                2 => $row['placement'] % 100 === 12 ? 'th' : 'nd',
                3 => $row['placement'] % 100 === 13 ? 'th' : 'rd',
                default => 'th',
            };
        @endphp
        <div class="mb-8 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">

            {{-- Entry header panel --}}
            <div class="bg-gray-50 dark:bg-gray-900 px-5 py-4 flex justify-between items-start border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-semibold">
                        {{ $row['entry']->style?->code }} · {{ $row['entry']->style?->name }}
                    </h3>
                    <div class="flex gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        @if($row['entry']->abv)<span>{{ $row['entry']->abv }}% ABV</span>@endif
                        <span>Bottle #{{ $row['entry']->bottle_number }}</span>
                        <span>{{ $row['sheet_count'] }} {{ Str::plural('judge', $row['sheet_count']) }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="flex items-baseline justify-end gap-1">
                        <span class="text-3xl font-bold">{{ number_format($row['avg_total'], 1) }}</span>
                        <span class="text-gray-400 dark:text-gray-500 text-sm">/ 50</span>
                    </div>
                    <div class="flex items-center justify-end gap-2 mt-1.5">
                        <span class="text-sm font-bold px-2.5 py-1 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                            {{ $row['placement'] }}{{ $ordinal }} of {{ $rankedEntries->count() }}
                        </span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ \App\Services\CompetitionRankingService::bandColor($row['band_key']) }}">
                            {{ $row['band'] }}
                        </span>
                    </div>
                </div>
            </div>

            @if($row['sheets']->isNotEmpty())
            {{-- Judge score table --}}
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">
                        <th class="text-left py-2 px-3 font-medium">Judge</th>
                        <th class="text-center py-2 px-3 font-medium">Aroma<br><span class="text-[10px] font-normal">/12</span></th>
                        <th class="text-center py-2 px-3 font-medium">App.<br><span class="text-[10px] font-normal">/3</span></th>
                        <th class="text-center py-2 px-3 font-medium">Flavour<br><span class="text-[10px] font-normal">/20</span></th>
                        <th class="text-center py-2 px-3 font-medium">Mouth.<br><span class="text-[10px] font-normal">/5</span></th>
                        <th class="text-center py-2 px-3 font-medium">Overall<br><span class="text-[10px] font-normal">/10</span></th>
                        <th class="text-center py-2 px-3 font-medium">Total</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                @foreach($row['sheets'] as $sheet)
                @php
                    $hasNotes = $sheet->aroma_notes || $sheet->appearance_notes || $sheet->flavour_notes || $sheet->mouthfeel_notes || $sheet->overall_notes;
                @endphp
                <tbody @if($hasNotes) x-data="{ open: false }" @endif>
                    <tr
                        class="border-t border-gray-100 dark:border-gray-800 {{ $hasNotes ? 'hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer' : '' }}"
                        @if($hasNotes) @click="open = !open" @endif
                    >
                        <td class="py-2.5 px-3 font-medium">{{ $sheet->judge?->name }}</td>
                        <td class="text-center py-2.5 px-3 tabular-nums">{{ $sheet->aroma }}</td>
                        <td class="text-center py-2.5 px-3 tabular-nums">{{ $sheet->appearance }}</td>
                        <td class="text-center py-2.5 px-3 tabular-nums">{{ $sheet->flavour }}</td>
                        <td class="text-center py-2.5 px-3 tabular-nums">{{ $sheet->mouthfeel }}</td>
                        <td class="text-center py-2.5 px-3 tabular-nums">{{ $sheet->overall }}</td>
                        <td class="text-center py-2.5 px-3 font-semibold tabular-nums">{{ $sheet->total }}</td>
                        <td class="text-center py-2.5 px-1 text-gray-400 text-xs chevron">
                            @if($hasNotes)
                                <span x-text="open ? '▴' : '▾'">▾</span>
                            @endif
                        </td>
                    </tr>
                    @if($hasNotes)
                    <tr x-show="open" class="notes-row bg-gray-50/60 dark:bg-gray-900/40" style="display: none;">
                        <td colspan="8" class="px-5 py-4">
                            <div class="max-w-2xl space-y-2.5 text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                @if($sheet->aroma_notes)<p><span class="inline-block w-20 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide align-top">Aroma</span> {{ $sheet->aroma_notes }}</p>@endif
                                @if($sheet->appearance_notes)<p><span class="inline-block w-20 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide align-top">Appearance</span> {{ $sheet->appearance_notes }}</p>@endif
                                @if($sheet->flavour_notes)<p><span class="inline-block w-20 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide align-top">Flavour</span> {{ $sheet->flavour_notes }}</p>@endif
                                @if($sheet->mouthfeel_notes)<p><span class="inline-block w-20 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide align-top">Mouthfeel</span> {{ $sheet->mouthfeel_notes }}</p>@endif
                                @if($sheet->overall_notes)<p><span class="inline-block w-20 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide align-top">Overall</span> {{ $sheet->overall_notes }}</p>@endif
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
                @endforeach
            </table>
            <p class="no-print text-xs text-gray-400 dark:text-gray-500 px-3 py-2 border-t border-gray-100 dark:border-gray-800">Click a row with notes to read them in full.</p>
            @else
            <p class="text-sm text-gray-500 dark:text-gray-400 italic px-5 py-4">No submitted scoresheets.</p>
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
                <tr class="border-t border-gray-100 dark:border-gray-800 {{ $row['entry']->member_id === $memberId ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}">
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
