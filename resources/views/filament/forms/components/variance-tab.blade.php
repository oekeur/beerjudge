@php
    use App\Models\Entry;
    use App\Models\Scoresheet;
    use Illuminate\Support\Facades\DB;

    $entriesTable = (new Entry)->getTable();
    $sheetsTable = (new Scoresheet)->getTable();

    $entries = Entry::query()
        ->where('competition_id', $record->id)
        ->withCount(['scoresheets as sheet_count' => fn ($q) => $q->where('status', 'submitted')])
        ->withAvg(['scoresheets as avg_total' => fn ($q) => $q->where('status', 'submitted')], 'total')
        ->addSelect([
            "{$entriesTable}.*",
            DB::raw("(SELECT MAX(s.total) - MIN(s.total) FROM {$sheetsTable} s WHERE s.entry_id = {$entriesTable}.id AND s.status = 'submitted') as spread"),
        ])
        ->whereRaw("(select count(*) from {$sheetsTable} where {$entriesTable}.id = {$sheetsTable}.entry_id and status = ?) >= 2", ['submitted'])
        ->orderBy('bottle_number')
        ->get();
@endphp

<div class="space-y-4">
    @if($record->status === 'closed')
        <div class="flex items-center gap-3">
            <a href="{{ route('results.index', $record) }}"
               target="_blank"
               class="fi-btn fi-btn-size-md fi-btn-color-gray fi-color-gray fi-ac-btn-action inline-flex items-center justify-center gap-1.5 font-semibold rounded-lg px-3 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10">
                View Results
            </a>
            @if(!$record->published_at)
                <span class="text-sm text-gray-500 dark:text-gray-400">Results not yet published to members.</span>
            @else
                <span class="text-sm text-gray-500 dark:text-gray-400">Published {{ $record->published_at->format('d M Y') }}.</span>
            @endif
        </div>
    @endif

    <div>
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white mb-3">Judge Score Variance</h3>

        @if($entries->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No entries with two or more submitted scoresheets yet.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
                <table class="w-full table-auto text-sm text-left">
                    <thead class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">#</th>
                            <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Sheets</th>
                            <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Avg Score</th>
                            <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">Spread</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($entries as $entry)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $entry->bottle_number ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $entry->sheet_count }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ round($entry->avg_total, 1) }}</td>
                                <td class="px-4 py-2">
                                    @if($entry->spread > 7)
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30">
                                            ⚠ {{ $entry->spread }} pts
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                            {{ $entry->spread }} pts
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
