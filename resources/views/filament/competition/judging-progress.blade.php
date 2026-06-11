@php
    $judges = $competition->judges->sortBy('name');
    $entries = $competition->entries->sortBy('bottle_number');

    $scoreSheetMap = $entries
        ->flatMap->scoresheets
        ->filter(fn ($s) => $judges->contains('id', $s->judge_id))
        ->keyBy(fn ($s) => $s->entry_id . '_' . $s->judge_id);
@endphp

<h3 class="text-sm font-semibold text-gray-950 dark:text-white mb-3">{{ __('competition.judging_progress.heading') }}</h3>

@if($judges->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('competition.judging_progress.no_judges') }}</p>
@elseif($entries->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('competition.judging_progress.no_entries') }}</p>
@else
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
        <table class="min-w-max text-sm text-left">
            <thead class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">#</th>
                    <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ __('competition.judging_progress.column.brewer') }}</th>
                    <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ __('competition.judging_progress.column.style') }}</th>
                    @foreach($judges as $judge)
                        <th class="px-3 py-2 font-medium text-gray-600 dark:text-gray-400 text-center whitespace-nowrap">{{ $judge->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach($entries as $entry)
                    <tr class="bg-white dark:bg-gray-900">
                        <td class="px-3 py-2 tabular-nums text-gray-950 dark:text-white whitespace-nowrap">{{ $entry->bottle_number ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $entry->member->name }}</td>
                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $entry->style->code }}</td>
                        @foreach($judges as $judge)
                            @php
                                $isOwnEntry = $entry->member_id === $judge->id;
                                $sheet = $scoreSheetMap->get($entry->id . '_' . $judge->id);
                            @endphp
                            <td class="px-3 py-2 text-center whitespace-nowrap {{ $isOwnEntry ? 'bg-gray-50 dark:bg-white/5' : '' }}">
                                @if($isOwnEntry)
                                    <span class="text-gray-300 dark:text-gray-600" title="{{ __('competition.judging_progress.own_entry') }}">·</span>
                                @elseif(! $sheet)
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @elseif($sheet->status === 'submitted')
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                        {{ __('competition.judging_progress.status.submitted') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30">
                                        {{ __('competition.judging_progress.status.draft') }}
                                    </span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
