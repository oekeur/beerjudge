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
            <tr class="border-t border-gray-100 dark:border-gray-800 {{ $row['entry']->member_id === $currentMemberId ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}">
                <td class="py-2 px-3 font-bold text-gray-900 dark:text-white">{{ $row['placement'] }}</td>
                <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $row['entry']->member?->name }}</td>
                <td class="py-2 px-3 text-gray-600 dark:text-gray-400">{{ $row['entry']->style?->code }}@if($row['entry']->abv)<span class="text-xs text-gray-400 dark:text-gray-500 ml-1">{{ $row['entry']->abv }}% ABV</span>@endif</td>
                <td class="text-center py-2 px-3 font-semibold text-gray-900 dark:text-white">{{ number_format($row['avg_total'], 1) }}</td>
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
