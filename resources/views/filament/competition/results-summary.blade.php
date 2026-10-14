@php
    /** @var \App\Models\Competition $competition */
    $entries = \App\Models\Entry::where('competition_id', $competition?->id)
        ->with([
            'member',
            'style',
            'scoresheets' => fn (\Illuminate\Database\Eloquent\Relations\Relation $query) => $query->where('status', \App\Enums\ScoresheetStatus::Submitted),
        ])
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();

    $allJudges   = $competition?->judges ?? collect();
    $judgeCount  = $allJudges->count();
    $entryCount  = $entries->count();
    $progressByEntry = $competition ? app(\App\Services\JudgingProgressService::class)->forCompetition($competition) : [];
    $sheetCount  = collect($progressByEntry)->sum('submitted');
    $maxSheets   = collect($progressByEntry)->sum('eligible');
    $completePct = $maxSheets > 0 ? min(100, (int) round($sheetCount / $maxSheets * 100)) : 0;

@endphp

<div class="space-y-6" @if ($competition) wire:poll.5s @endif>
@if (! $competition)
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('widget.summary.competition_gone') }}</p>
@else

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach([
            ['label' => __('widget.summary.entries'),   'value' => $entryCount],
            ['label' => __('widget.summary.judges'),    'value' => $judgeCount],
            ['label' => __('widget.summary.sheets'),    'value' => $maxSheets > 0 ? "{$sheetCount} / {$maxSheets}" : '—'],
            ['label' => __('widget.summary.progress'),  'value' => $maxSheets > 0 ? "{$completePct}%" : '—'],
        ] as $stat)
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/5">
                <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ (string) $stat['value'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ (string) $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('results.index', $competition) }}"
           target="_blank"
           class="fi-btn fi-btn-size-md fi-btn-color-gray fi-color-gray fi-ac-btn-action inline-flex items-center justify-center gap-1.5 font-semibold rounded-lg px-3 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10">
            {{ __('widget.summary.view_results') }}
        </a>
        @if(! $competition->isClosed())
            <a href="{{ route('judging.show', $competition) }}"
               target="_blank"
               class="fi-btn fi-btn-size-md fi-btn-color-gray fi-color-gray fi-ac-btn-action inline-flex items-center justify-center gap-1.5 font-semibold rounded-lg px-3 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10">
                {{ __('widget.summary.open_judging') }}
            </a>
            <span class="text-xs text-warning-600 dark:text-warning-400">{{ __('widget.summary.judging_in_progress') }}</span>
        @endif
    </div>

    {{-- Per-entry progress --}}
    @if($entries->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('widget.summary.no_entries') }}</p>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full table-auto text-sm text-left">
                <thead class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">#</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">{{ __('widget.summary.column.brewer') }}</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">{{ __('widget.summary.column.style') }}</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">{{ __('widget.summary.column.complete') }}</th>
                        <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-400">{{ __('widget.summary.column.missing') }}</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($entries as $entry)
                        @php
                            $entryProgress = $progressByEntry[$entry->id];
                            $missingJudges = $allJudges->whereIn('id', $entryProgress['missing']);
                            $submitted     = $entryProgress['submitted'];
                            $eligibleCount = $entryProgress['eligible'];
                        @endphp
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $entry->bottle_number ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $entry->member?->name }}</td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $entry->style?->code }}</td>
                            <td class="px-4 py-2">
                                @if($eligibleCount > 0)
                                    @if($submitted === $eligibleCount)
                                        <span class="font-semibold text-success-700 dark:text-success-400">{{ $submitted }}/{{ $eligibleCount }}</span>
                                    @else
                                        <span class="text-gray-700 dark:text-gray-300">{{ $submitted }}/{{ $eligibleCount }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($eligibleCount === 0)
                                    <span class="text-gray-400">—</span>
                                @elseif($missingJudges->isEmpty())
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                        {{ __('widget.summary.done') }}
                                    </span>
                                @else
                                    <span class="text-warning-700 dark:text-warning-400">{{ $missingJudges->map(fn (\App\Models\Member $m): string => $m->name)->join(', ') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('results.show', [$competition, $entry->member]) }}"
                                   target="_blank"
                                   class="text-xs text-primary-600 hover:underline dark:text-primary-400">
                                    {{ __('widget.summary.sheet') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

@endif
</div>
