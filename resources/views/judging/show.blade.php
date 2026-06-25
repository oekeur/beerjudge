<x-page :title="'Judging · ' . $competition->name" :vite="['resources/css/app.css', 'resources/js/judging.js']">
    <x-slot:head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </x-slot:head>
<body class="bg-stone-100 dark:bg-gray-950 min-h-screen" x-data="judgingApp(JSON.parse($el.dataset.server))" x-init="init()" data-server='@json($serverData)'>

    {{-- Header --}}
    <header class="bg-amber-700 text-white sticky top-0 z-10 shadow">
        {{-- Navigation row --}}
        <div class="px-4 pt-2 pb-1 flex items-center justify-between gap-2 border-b border-amber-600/50">
            <a href="{{ route('home') }}" class="flex items-center gap-1 text-amber-200 text-sm hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7A1 1 0 003 11h1v6a1 1 0 001 1h4a1 1 0 001-1v-3h2v3a1 1 0 001 1h4a1 1 0 001-1v-6h1a1 1 0 00.707-1.707l-7-7z" />
                </svg>
                <span>{{ __('nav.home') }}</span>
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('profile.edit') }}" class="text-amber-200 text-sm hover:text-white">
                    {{ __('nav.profile') }}
                </a>
                @if($judge->is_admin)
                    <a href="/admin" class="text-amber-200 text-sm hover:text-white">
                        {{ __('nav.admin') }}
                    </a>
                @endif
                <x-theme-toggle />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-amber-200 text-sm hover:text-white">{{ __('nav.logout') }}</button>
                </form>
            </div>
        </div>
        {{-- Context row --}}
        <div class="px-4 py-2 flex items-center justify-between gap-3">
            <div>
                <div class="font-bold text-base leading-tight">{{ $competition->name }}</div>
                <div class="text-amber-200 text-xs">{{ $judge->name }}</div>
            </div>
            @if($judge->is_admin)
                <div class="flex items-center gap-3 shrink-0">
                    <a href="/admin/competitions/{{ $competition->id }}/edit?tab=entries" class="text-amber-200 text-sm hover:text-white">
                        {{ __('nav.entries') }}
                    </a>
                    <a href="/admin/competitions/{{ $competition->id }}/edit?tab=dashboard" class="text-amber-200 text-sm hover:text-white">
                        {{ __('nav.competition_dashboard') }}
                    </a>
                </div>
            @endif
        </div>
    </header>

    {{-- Judge registration banner --}}
    @if(! $isJudge)
        <div class="bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800/50 px-4 py-3 flex items-center justify-between gap-4">
            <p class="text-sm text-amber-800 dark:text-amber-300">{{ __('judging.not_registered') }}</p>
            <form method="POST" action="{{ route('competitions.judges.store', $competition) }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-amber-700 dark:text-amber-400 underline whitespace-nowrap">
                    {{ __('judging.register_as_judge') }}
                </button>
            </form>
        </div>
    @else
        <div class="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 px-4 py-2 flex justify-end">
            <form method="POST" action="{{ route('competitions.judges.destroy', $competition) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-gray-400 dark:text-gray-500 underline">{{ __('judging.leave_as_judge') }}</button>
            </form>
        </div>
    @endif

    {{-- Entry list --}}
    <main class="max-w-lg mx-auto px-4 py-4 space-y-3">

        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-400">{{ __('judging.sort_label') }}</span>
            <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden text-xs font-medium">
                <button type="button"
                    class="px-3 py-1.5 transition-colors"
                    :class="sortMode === 'judging' ? 'bg-amber-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    @click="sortMode = 'judging'"
                >{{ __('judging.sort_judging') }}</button>
                <button type="button"
                    class="px-3 py-1.5 border-l border-gray-200 dark:border-gray-700 transition-colors"
                    :class="sortMode === 'ranked' ? 'bg-amber-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    @click="sortMode = 'ranked'"
                >{{ __('judging.sort_ranked') }}</button>
            </div>
        </div>

        <template x-if="entries.length === 0">
            <p class="text-gray-500 dark:text-gray-400 text-center py-12">{{ __('judging.no_entries') }}</p>
        </template>

        <template x-for="(entry, index) in sortedEntries" :key="entry.id">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

                {{-- Entry row header --}}
                <button
                    type="button"
                    class="w-full text-left px-4 py-4 flex items-center gap-3"
                    data-testid="entry-expand-button"
                    @click="toggleExpanded(entry.id)"
                >
                    <div class="w-6 text-right flex-shrink-0">
                        <span x-show="sortMode === 'judging'" class="text-xs text-gray-300 tabular-nums" x-text="'#' + entry.tasting_number"></span>
                        <span x-show="sortMode === 'ranked'" class="text-xs text-gray-400 tabular-nums font-medium" x-text="(index + 1) + '.'"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 dark:text-white truncate text-sm" x-text="entry.brewer_name"></div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 flex items-center gap-1 min-w-0 transition-colors"
                            :class="sheets[entry.id]?.expanded ? 'cursor-pointer hover:text-amber-600 dark:hover:text-amber-400' : ''"
                            :role="sheets[entry.id]?.expanded ? 'button' : undefined"
                            :tabindex="sheets[entry.id]?.expanded ? '0' : '-1'"
                            @click.stop="if (sheets[entry.id]?.expanded) openStyleInfo(entry.id)"
                            @keydown.enter.stop="if (sheets[entry.id]?.expanded) openStyleInfo(entry.id)"
                            :aria-label="sheets[entry.id]?.expanded ? 'View style info for ' + entry.style_name : undefined"
                        >
                            <span class="truncate" x-text="entry.style_name"></span>
                            <span x-show="sheets[entry.id]?.expanded" class="w-4 h-4 rounded-full border border-amber-300 dark:border-amber-600 text-amber-600 dark:text-amber-400 text-[10px] font-bold leading-none flex items-center justify-center flex-shrink-0">i</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        {{-- Score preview --}}
                        <span class="text-sm font-semibold tabular-nums"
                            :style="{ color: scoreColor(sheetTotal(entry.id)) }"
                            x-show="sheets[entry.id]?.status === 'submitted'"
                            x-text="sheetTotal(entry.id) + ' / 50'">
                        </span>
                        <span class="text-sm tabular-nums text-gray-400 dark:text-gray-500"
                            x-show="sheets[entry.id]?.status === 'draft' && sheetTotal(entry.id) > 0"
                            x-text="sheetTotal(entry.id) + ' / 50'">
                        </span>

                        {{-- Sync badge --}}
                        <span x-show="!sheets[entry.id] || sheets[entry.id].syncStatus === 'idle'" class="text-xs text-gray-300 dark:text-gray-600">—</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'syncing' || sheets[entry.id]?.syncStatus === 'pending'" class="text-xs text-amber-500 animate-pulse">{{ __('judging.saving') }}</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'synced' && sheets[entry.id]?.status === 'submitted'" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ __('judging.submitted') }}</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'synced' && sheets[entry.id]?.status === 'draft'" class="text-xs text-gray-400 dark:text-gray-500">{{ __('judging.saved') }}</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'error'" class="text-xs text-red-500 cursor-pointer" @click.stop="retryNow(entry.id)">{{ __('judging.retry') }}</span>

                        {{-- Expand chevron --}}
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-transform flex-shrink-0" :class="{ 'rotate-180': sheets[entry.id]?.expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                {{-- Scoresheet (expanded) --}}
                <div x-show="sheets[entry.id]?.expanded" x-transition class="border-t border-gray-100 dark:border-gray-800 px-4 pb-4 pt-3">

                    {{-- Submitted read-only view --}}
                    <template x-if="sheets[entry.id]?.status === 'submitted' && sheets[entry.id]?.syncStatus === 'synced'">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold text-sm" data-testid="scoresheet-submitted">{{ __('judging.submitted') }}</span>
                            </div>
                            <table class="text-sm text-gray-600 dark:text-gray-400 mb-3" style="border-spacing: 0" aria-live="polite">
                                <tr><td class="pr-6">{{ __('judging.section.aroma') }}</td><td><span class="inline-block text-right tabular-nums" style="min-width: 1.5em"><strong x-text="sheets[entry.id].aroma"></strong></span>/12</td></tr>
                                <tr><td class="pr-6">{{ __('judging.section.appearance') }}</td><td><span class="inline-block text-right tabular-nums" style="min-width: 1.5em"><strong x-text="sheets[entry.id].appearance"></strong></span>/3</td></tr>
                                <tr><td class="pr-6">{{ __('judging.section.flavour') }}</td><td><span class="inline-block text-right tabular-nums" style="min-width: 1.5em"><strong x-text="sheets[entry.id].flavour"></strong></span>/20</td></tr>
                                <tr><td class="pr-6">{{ __('judging.section.mouthfeel') }}</td><td><span class="inline-block text-right tabular-nums" style="min-width: 1.5em"><strong x-text="sheets[entry.id].mouthfeel"></strong></span>/5</td></tr>
                                <tr><td class="pr-6">{{ __('judging.section.overall') }}</td><td><span class="inline-block text-right tabular-nums" style="min-width: 1.5em"><strong x-text="sheets[entry.id].overall"></strong></span>/10</td></tr>
                            </table>
                            <div class="mb-4">
                                <div class="text-base font-bold text-gray-900 dark:text-white">{{ __('judging.total') }} <span x-text="sheetTotal(entry.id)"></span> / 50</div>
                                <div class="mt-0.5">
                                    <span class="text-sm font-semibold"
                                        :class="{
                                            'text-emerald-600 dark:text-emerald-400': sheetTotal(entry.id) >= 38,
                                            'text-lime-600 dark:text-lime-400': sheetTotal(entry.id) >= 30 && sheetTotal(entry.id) < 38,
                                            'text-amber-600 dark:text-amber-400': sheetTotal(entry.id) >= 21 && sheetTotal(entry.id) < 30,
                                            'text-red-600 dark:text-red-400': sheetTotal(entry.id) < 21
                                        }"
                                        x-text="bandInfo(sheetTotal(entry.id)).name"></span><span class="text-sm text-gray-500 dark:text-gray-400 ml-1.5" x-text="'(' + bandInfo(sheetTotal(entry.id)).range + ')'"></span>
                                    <div class="text-sm text-gray-600 dark:text-gray-400" x-text="bandInfo(sheetTotal(entry.id)).description"></div>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="text-sm text-amber-700 dark:text-amber-400 underline"
                                @click="reopenSheet(entry.id)"
                            >{{ __('judging.reopen') }}</button>
                        </div>
                    </template>

                    {{-- Editable scoresheet --}}
                    <template x-if="!sheets[entry.id] || sheets[entry.id]?.status !== 'submitted' || sheets[entry.id]?.syncStatus !== 'synced'">
                        <div>
                            {{-- Aroma --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <button type="button" class="flex items-center gap-1.5 hover:text-amber-700 dark:hover:text-amber-400 group" @click.stop="openGuide(entry.id, 'aroma')" :aria-label="'View BJCP aroma guideline for ' + entry.style_name">
                                        <span class="font-medium text-gray-800 dark:text-gray-200 text-sm group-hover:text-amber-700 dark:group-hover:text-amber-400">{{ __('judging.section.aroma') }}</span>
                                        <span class="w-4 h-4 rounded-full border border-amber-300 dark:border-amber-600 text-amber-600 dark:text-amber-400 text-[10px] font-bold leading-none flex items-center justify-center group-hover:bg-amber-50 dark:group-hover:bg-amber-900/30 flex-shrink-0">i</span>
                                    </button>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700 dark:text-amber-400"><span x-text="sheets[entry.id].aroma !== null ? sheets[entry.id].aroma : '—'"></span> / 12</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Aroma score, 0 to 12"
                                    :class="sheets[entry.id].submitAttempted && sheets[entry.id].aroma === null ? 'ring-2 ring-red-400 rounded-lg p-1' : ''">
                                    @for ($n = 0; $n <= 12; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].aroma === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                                        @click="sheets[entry.id].aroma = {{ $n }}; sheets[entry.id].submitAttempted = false; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].aroma === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea :id="'aroma-notes-' + entry.id" class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 resize-none" rows="2" placeholder="{{ __('judging.placeholder.aroma') }}" x-model="sheets[entry.id].aroma_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Aroma notes'"></textarea>
                            </div>

                            {{-- Appearance --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <button type="button" class="flex items-center gap-1.5 hover:text-amber-700 dark:hover:text-amber-400 group" @click.stop="openGuide(entry.id, 'appearance')" :aria-label="'View BJCP appearance guideline for ' + entry.style_name">
                                        <span class="font-medium text-gray-800 dark:text-gray-200 text-sm group-hover:text-amber-700 dark:group-hover:text-amber-400">{{ __('judging.section.appearance') }}</span>
                                        <span class="w-4 h-4 rounded-full border border-amber-300 dark:border-amber-600 text-amber-600 dark:text-amber-400 text-[10px] font-bold leading-none flex items-center justify-center group-hover:bg-amber-50 dark:group-hover:bg-amber-900/30 flex-shrink-0">i</span>
                                    </button>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700 dark:text-amber-400"><span x-text="sheets[entry.id].appearance !== null ? sheets[entry.id].appearance : '—'"></span> / 3</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Appearance score, 0 to 3"
                                    :class="sheets[entry.id].submitAttempted && sheets[entry.id].appearance === null ? 'ring-2 ring-red-400 rounded-lg p-1' : ''">
                                    @for ($n = 0; $n <= 3; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].appearance === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                                        @click="sheets[entry.id].appearance = {{ $n }}; sheets[entry.id].submitAttempted = false; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].appearance === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 resize-none" rows="2" placeholder="{{ __('judging.placeholder.appearance') }}" x-model="sheets[entry.id].appearance_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Appearance notes'"></textarea>
                            </div>

                            {{-- Flavour --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <button type="button" class="flex items-center gap-1.5 hover:text-amber-700 dark:hover:text-amber-400 group" @click.stop="openGuide(entry.id, 'flavor')" :aria-label="'View BJCP flavour guideline for ' + entry.style_name">
                                        <span class="font-medium text-gray-800 dark:text-gray-200 text-sm group-hover:text-amber-700 dark:group-hover:text-amber-400">{{ __('judging.section.flavour') }}</span>
                                        <span class="w-4 h-4 rounded-full border border-amber-300 dark:border-amber-600 text-amber-600 dark:text-amber-400 text-[10px] font-bold leading-none flex items-center justify-center group-hover:bg-amber-50 dark:group-hover:bg-amber-900/30 flex-shrink-0">i</span>
                                    </button>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700 dark:text-amber-400"><span x-text="sheets[entry.id].flavour !== null ? sheets[entry.id].flavour : '—'"></span> / 20</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Flavour score, 0 to 20"
                                    :class="sheets[entry.id].submitAttempted && sheets[entry.id].flavour === null ? 'ring-2 ring-red-400 rounded-lg p-1' : ''">
                                    @for ($n = 0; $n <= 20; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].flavour === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                                        @click="sheets[entry.id].flavour = {{ $n }}; sheets[entry.id].submitAttempted = false; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].flavour === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 resize-none" rows="2" placeholder="{{ __('judging.placeholder.flavour') }}" x-model="sheets[entry.id].flavour_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Flavour notes'"></textarea>
                            </div>

                            {{-- Mouthfeel --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <button type="button" class="flex items-center gap-1.5 hover:text-amber-700 dark:hover:text-amber-400 group" @click.stop="openGuide(entry.id, 'mouthfeel')" :aria-label="'View BJCP mouthfeel guideline for ' + entry.style_name">
                                        <span class="font-medium text-gray-800 dark:text-gray-200 text-sm group-hover:text-amber-700 dark:group-hover:text-amber-400">{{ __('judging.section.mouthfeel') }}</span>
                                        <span class="w-4 h-4 rounded-full border border-amber-300 dark:border-amber-600 text-amber-600 dark:text-amber-400 text-[10px] font-bold leading-none flex items-center justify-center group-hover:bg-amber-50 dark:group-hover:bg-amber-900/30 flex-shrink-0">i</span>
                                    </button>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700 dark:text-amber-400"><span x-text="sheets[entry.id].mouthfeel !== null ? sheets[entry.id].mouthfeel : '—'"></span> / 5</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Mouthfeel score, 0 to 5"
                                    :class="sheets[entry.id].submitAttempted && sheets[entry.id].mouthfeel === null ? 'ring-2 ring-red-400 rounded-lg p-1' : ''">
                                    @for ($n = 0; $n <= 5; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].mouthfeel === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                                        @click="sheets[entry.id].mouthfeel = {{ $n }}; sheets[entry.id].submitAttempted = false; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].mouthfeel === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 resize-none" rows="2" placeholder="{{ __('judging.placeholder.mouthfeel') }}" x-model="sheets[entry.id].mouthfeel_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Mouthfeel notes'"></textarea>
                            </div>

                            {{-- Overall --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <button type="button" class="flex items-center gap-1.5 hover:text-amber-700 dark:hover:text-amber-400 group" @click.stop="openGuide(entry.id, 'overall_impression')" :aria-label="'View BJCP overall impression guideline for ' + entry.style_name">
                                        <span class="font-medium text-gray-800 dark:text-gray-200 text-sm group-hover:text-amber-700 dark:group-hover:text-amber-400">{{ __('judging.section.overall') }}</span>
                                        <span class="w-4 h-4 rounded-full border border-amber-300 dark:border-amber-600 text-amber-600 dark:text-amber-400 text-[10px] font-bold leading-none flex items-center justify-center group-hover:bg-amber-50 dark:group-hover:bg-amber-900/30 flex-shrink-0">i</span>
                                    </button>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700 dark:text-amber-400"><span x-text="sheets[entry.id].overall !== null ? sheets[entry.id].overall : '—'"></span> / 10</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Overall Impression score, 0 to 10"
                                    :class="sheets[entry.id].submitAttempted && sheets[entry.id].overall === null ? 'ring-2 ring-red-400 rounded-lg p-1' : ''">
                                    @for ($n = 0; $n <= 10; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].overall === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                                        @click="sheets[entry.id].overall = {{ $n }}; sheets[entry.id].submitAttempted = false; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].overall === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 resize-none" rows="2" placeholder="{{ __('judging.placeholder.overall') }}" x-model="sheets[entry.id].overall_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Overall impression notes'"></textarea>
                            </div>

                            {{-- Off-Flavor Descriptors --}}
                            <div x-data="{ descriptorsOpen: false }" class="mb-4">
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                                    @click="descriptorsOpen = !descriptorsOpen"
                                >
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-90': descriptorsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    {{ __('judging.off_flavors') }}
                                    <template x-if="(sheets[entry.id]?.descriptors || []).length > 0">
                                        <span class="ml-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-xs font-medium px-1.5 py-0.5 rounded-full" x-text="(sheets[entry.id]?.descriptors || []).length"></span>
                                    </template>
                                </button>
                                <div x-show="descriptorsOpen" x-transition class="mt-3 grid grid-cols-1 gap-y-2">
                                    @foreach($descriptors as $descriptor)
                                    <label class="flex items-start gap-2.5 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500 flex-shrink-0"
                                            :checked="(sheets[entry.id]?.descriptors || []).includes('{{ $descriptor['slug'] }}')"
                                            @change="toggleDescriptor(entry.id, '{{ $descriptor['slug'] }}')"
                                        >
                                        <span class="text-sm leading-snug">
                                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $descriptor['name'] }}</span>
                                            <span class="text-gray-500 dark:text-gray-400"> — {{ $descriptor['description'] }}</span>
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Total + Submit --}}
                            <div class="border-t border-gray-100 dark:border-gray-800 pt-3 flex items-center justify-between gap-3" data-testid="scoresheet-footer">
                                <div>
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        <span x-text="sheetTotal(entry.id)"></span> / 50
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold"
                                            :class="{
                                                'text-emerald-600 dark:text-emerald-400': sheetTotal(entry.id) >= 38,
                                                'text-lime-600 dark:text-lime-400': sheetTotal(entry.id) >= 30 && sheetTotal(entry.id) < 38,
                                                'text-amber-600 dark:text-amber-400': sheetTotal(entry.id) >= 21 && sheetTotal(entry.id) < 30,
                                                'text-red-600 dark:text-red-400': sheetTotal(entry.id) < 21
                                            }"
                                            x-text="bandInfo(sheetTotal(entry.id)).name"></span><span class="text-xs text-gray-500 dark:text-gray-400 ml-1.5" x-text="'(' + bandInfo(sheetTotal(entry.id)).range + ')'"></span>
                                        <div class="text-xs text-gray-600 dark:text-gray-400" x-text="bandInfo(sheetTotal(entry.id)).description"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3" aria-live="polite">
                                    <span x-show="sheets[entry.id]?.syncStatus === 'pending' || sheets[entry.id]?.syncStatus === 'syncing'" class="text-xs text-amber-500 animate-pulse">{{ __('judging.saving') }}</span>
                                    <span x-show="sheets[entry.id]?.syncStatus === 'synced'" class="text-xs text-gray-400 dark:text-gray-500">{{ __('judging.saved') }}</span>
                                    <span x-show="sheets[entry.id]?.syncStatus === 'error'" class="text-xs text-red-500 cursor-pointer" @click="retryNow(entry.id)">{{ __('judging.retry') }}</span>
                                    <button
                                        type="button"
                                        class="bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700 active:scale-95 transition-transform"
                                        @click="submitSheet(entry.id)"
                                    >{{ __('judging.submit') }}</button>
                                </div>
                            </div>
                        </div>
                    </template>

                </div>
            </div>
        </template>
    </main>

    {{-- Style guide bottom sheet --}}
    <div x-show="styleGuide.open" class="fixed inset-0 z-50 flex items-end" @keydown.escape.window="closeGuide()" style="display: none;">
        <div class="absolute inset-0 bg-black/40" @click="closeGuide()"></div>
        <div class="relative w-full max-w-lg mx-auto bg-white dark:bg-gray-900 rounded-t-2xl shadow-xl px-5 pt-4 pb-8"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-0"
        >
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400" x-text="styleGuide.section"></span>
                <button type="button" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 text-xl leading-none" @click="closeGuide()">×</button>
            </div>
            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" x-text="styleGuide.text"></p>
            {{-- SRM color visualization (appearance section only) --}}
            <div x-show="styleGuide.srm !== null" class="mt-4">
                <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Color (SRM)</div>
                <div class="px-4">
                    <div class="relative" style="height: 88px;">
                        {{-- Gradient strip (top:20px so foam floats above it) --}}
                        <div class="absolute inset-x-0 rounded-lg" :style="styleGuide.srm?.gradientStyle"></div>
                        {{-- Left opacity mask --}}
                        <div x-show="styleGuide.srm?.hasBoth" class="absolute left-0 rounded-l-lg bg-white opacity-60" :style="'top:20px;height:24px;width:' + styleGuide.srm?.leftMaskPct"></div>
                        {{-- Right opacity mask --}}
                        <div x-show="styleGuide.srm?.hasBoth" class="absolute right-0 rounded-r-lg bg-white opacity-60" :style="'top:20px;height:24px;width:' + styleGuide.srm?.rightMaskPct"></div>
                        {{-- Min glass --}}
                        <div x-show="styleGuide.srm?.srmMin !== null" class="absolute flex flex-col items-center" :style="'top:0;transform:translateX(-50%);left:' + styleGuide.srm?.leftMinPct">
                            <svg width="28" height="52" viewBox="0 0 28 52" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-gray-300 dark:text-gray-600" style="filter:drop-shadow(0 1px 2px rgba(0,0,0,0.4))">
                                <path d="M4 8 L6 46 Q6 48 14 48 Q22 48 22 46 L24 8 Z" :fill="styleGuide.srm?.minFill" stroke="currentColor" stroke-width="1.2"/>
                                <path d="M4 8 Q6 4 14 4 Q22 4 24 8 L23 12 Q20 9 14 9 Q8 9 5 12 Z" fill="#f5f0e8" stroke="currentColor" stroke-width="1"/>
                                <rect x="7" y="10" width="3" height="28" rx="1.5" fill="white" fill-opacity="0.18"/>
                            </svg>
                            <span class="text-xs text-amber-700 dark:text-amber-400 mt-0.5 tabular-nums whitespace-nowrap" x-text="'SRM ' + styleGuide.srm?.srmMin"></span>
                        </div>
                        {{-- Max glass (only when hasBoth and different from min) --}}
                        <div x-show="styleGuide.srm?.hasBoth && styleGuide.srm?.srmMax !== styleGuide.srm?.srmMin" class="absolute flex flex-col items-center" :style="'top:0;transform:translateX(-50%);left:' + styleGuide.srm?.leftMaxPct">
                            <svg width="28" height="52" viewBox="0 0 28 52" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-gray-300 dark:text-gray-600" style="filter:drop-shadow(0 1px 2px rgba(0,0,0,0.4))">
                                <path d="M4 8 L6 46 Q6 48 14 48 Q22 48 22 46 L24 8 Z" :fill="styleGuide.srm?.maxFill" stroke="currentColor" stroke-width="1.2"/>
                                <path d="M4 8 Q6 4 14 4 Q22 4 24 8 L23 12 Q20 9 14 9 Q8 9 5 12 Z" fill="#f5f0e8" stroke="currentColor" stroke-width="1"/>
                                <rect x="7" y="10" width="3" height="28" rx="1.5" fill="white" fill-opacity="0.18"/>
                            </svg>
                            <span class="text-xs text-amber-700 dark:text-amber-400 mt-0.5 tabular-nums whitespace-nowrap" x-text="'SRM ' + styleGuide.srm?.srmMax"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Style info bottom sheet (category description, history, style comparison) --}}
    <div x-show="styleInfo.open" class="fixed inset-0 z-50 flex items-end" @keydown.escape.window="closeStyleInfo()" style="display: none;">
        <div class="absolute inset-0 bg-black/40" @click="closeStyleInfo()"></div>
        <div class="relative w-full max-w-lg mx-auto bg-white dark:bg-gray-900 rounded-t-2xl shadow-xl"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-0"
        >
            <div class="flex justify-center pt-3 pb-1">
                <div class="w-8 h-1 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
            </div>
            <div class="flex items-start justify-between px-5 pt-2 pb-3 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400" x-text="styleInfo.styleCode + ' · ' + styleInfo.styleName"></div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5" x-text="styleInfo.categoryName"></div>
                </div>
                <button type="button" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 text-xl leading-none ml-4 mt-0.5" @click="closeStyleInfo()">×</button>
            </div>
            <div class="overflow-y-auto px-5 pt-4 pb-8 space-y-5" style="max-height: 60vh">
                <template x-if="styleInfo.categoryDescription">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5">Category</div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" x-text="styleInfo.categoryDescription"></p>
                    </div>
                </template>
                <template x-if="styleInfo.history">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5">History</div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" x-text="styleInfo.history"></p>
                    </div>
                </template>
                <template x-if="styleInfo.styleComparison">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5">Style Comparison</div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" x-text="styleInfo.styleComparison"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>

</body>
</x-page>
