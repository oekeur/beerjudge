@php
    use App\Models\Entry;
    use Illuminate\Database\Eloquent\Relations\Relation;

    /** @var \App\Models\Competition $competition */
    $entries = Entry::where('competition_id', $competition->id)
        ->with([
            'member',
            'style',
            'scoresheets' => fn (Relation $query) => $query->where('status', 'submitted'),
        ])
        ->orderBy('bottle_number')
        ->get();

    $allJudges   = $competition->judges;
    $judgeCount  = $allJudges->count();
    $entryCount  = $entries->count();
    $sheetCount  = $entries->sum(fn ($e) => $e->scoresheets->count());
    $maxSheets   = $judgeCount * $entryCount;
    $completePct = $maxSheets > 0 ? (int) round($sheetCount / $maxSheets * 100) : 0;

    $rows = $entries->map(function (Entry $entry) use ($allJudges) {
        $eligible       = $allJudges->where('id', '!=', $entry->member_id);
        $submittedIds   = $entry->scoresheets->pluck('judge_id');
        $missingJudges  = $eligible->whereNotIn('id', $submittedIds);

        return [
            'entry'         => $entry,
            'submitted'     => $submittedIds->count(),
            'eligible'      => $eligible->count(),
            'missingJudges' => $missingJudges,
        ];
    });
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
            <span class="text-xs text-warning-600 dark:text-warning-400">Judging in progress.</span>
        @endif
    </div>

    {{-- Per-entry progress --}}
    @if($rows->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No entries registered yet.</p>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full table-auto text-sm text-left">
                <thead class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">#</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Brewer</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Style</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Complete</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Missing</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($rows as $row)
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $row['entry']->bottle_number ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $row['entry']->member?->name }}</td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $row['entry']->style?->code }}</td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                @if($row['eligible'] > 0)
                                    {{ $row['submitted'] }}/{{ $row['eligible'] }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($row['eligible'] === 0)
                                    <span class="text-gray-400">—</span>
                                @elseif($row['missingJudges']->isEmpty())
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                        Done
                                    </span>
                                @else
                                    <span class="text-warning-700 dark:text-warning-400">{{ $row['missingJudges']->pluck('name')->join(', ') }}</span>
                                @endif
                            </td>
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
