@php
    use App\Models\Entry;
    use App\Models\Scoresheet;
    use Illuminate\Database\Eloquent\Relations\Relation;

    /** @var \App\Models\Competition $competition */
    $entries = Entry::where('competition_id', $competition->id)
        ->with([
            'member',
            'style',
            'scoresheets' => fn (Relation $query) => $query->where('status', 'submitted'),
        ])
        ->orderBy('id')
        ->get();

    $judgeCount  = $competition->judges()->count();
    $entryCount  = $entries->count();
    $sheetCount  = $entries->sum(fn ($e) => $e->scoresheets->count());
    $maxSheets   = $judgeCount * $entryCount;
    $completePct = $maxSheets > 0 ? (int) round($sheetCount / $maxSheets * 100) : 0;

    $bandLabel = fn (?float $t): ?string => match (true) {
        $t === null      => null,
        $t >= 45         => 'Outstanding',
        $t >= 38         => 'Excellent',
        $t >= 30         => 'Very Good',
        $t >= 21         => 'Good',
        $t >= 14         => 'Fair',
        default          => 'Problematic',
    };

    $bandColor = fn (?string $b): string => match ($b) {
        'Outstanding' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
        'Excellent'   => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
        'Very Good'   => 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30',
        'Good'        => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
        'Fair'        => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30',
        'Problematic' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
        default       => 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
    };

    $ranked = $entries
        ->map(function ($entry) use ($bandLabel) {
            $sheets   = $entry->scoresheets;
            $avgTotal = $sheets->isNotEmpty() ? round((float) $sheets->avg('total'), 1) : null;
            $avgOverall = $sheets->isNotEmpty() ? round((float) $sheets->avg('overall'), 2) : null;
            return [
                'entry'      => $entry,
                'avg_total'  => $avgTotal,
                'avg_overall'=> $avgOverall,
                'sheet_count'=> $sheets->count(),
                'band'       => $bandLabel($avgTotal),
            ];
        })
        ->sortBy([
            fn ($a, $b) => ($b['avg_total'] ?? -1) <=> ($a['avg_total'] ?? -1),
            fn ($a, $b) => ($b['avg_overall'] ?? -1) <=> ($a['avg_overall'] ?? -1),
        ])
        ->values()
        ->map(fn ($row, $i) => array_merge($row, ['placement' => $i + 1]));
@endphp

<div class="space-y-6">

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach([
            ['label' => 'Entries',   'value' => $entryCount],
            ['label' => 'Judges',    'value' => $judgeCount],
            ['label' => 'Sheets',    'value' => $maxSheets > 0 ? "{$sheetCount} / {$maxSheets}" : '—'],
            ['label' => 'Progress',  'value' => $maxSheets > 0 ? "{$completePct}%" : '—'],
        ] as $stat)
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $stat['value'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('results.index', $competition) }}"
           target="_blank"
           class="fi-btn fi-btn-size-md fi-btn-color-gray fi-color-gray fi-ac-btn-action inline-flex items-center justify-center gap-1.5 font-semibold rounded-lg px-3 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10">
            View all results
        </a>
        @if($competition->status !== 'closed')
            <span class="text-xs text-warning-600 dark:text-warning-400">Preliminary — judging is still in progress.</span>
        @endif
    </div>

    {{-- Standings --}}
    @if($ranked->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No entries registered yet.</p>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full table-auto text-sm text-left">
                <thead class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">#</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Brewer</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Style</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Avg</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Band</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Sheets</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($ranked as $row)
                        @php
                            $badge = $bandColor($row['band']);
                        @endphp
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-2 font-medium text-gray-950 dark:text-white">{{ $row['avg_total'] !== null ? $row['placement'] : '—' }}</td>
                            <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $row['entry']->member->name }}</td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $row['entry']->style->code }}</td>
                            <td class="px-4 py-2 font-mono text-gray-950 dark:text-white">
                                {{ $row['avg_total'] !== null ? number_format($row['avg_total'], 1) : '—' }}
                            </td>
                            <td class="px-4 py-2">
                                @if($row['band'])
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                        {{ $row['band'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $row['sheet_count'] }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('results.show', [$competition, $row['entry']->member]) }}"
                                   target="_blank"
                                   class="text-xs text-primary-600 hover:underline dark:text-primary-400">
                                    Sheet
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
