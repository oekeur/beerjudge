<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Judging · {{ $competition->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/judging.js'])
</head>
<body class="bg-gray-50 min-h-screen" x-data="judgingApp(JSON.parse($el.dataset.server))" x-init="init()" data-server='@json($serverData)'>

    {{-- Header --}}
    <header class="bg-amber-700 text-white px-4 py-3 flex justify-between items-center sticky top-0 z-10 shadow">
        <div>
            <div class="font-bold text-lg leading-tight">{{ $competition->name }}</div>
            <div class="text-amber-200 text-sm">Hi, {{ $judge->name }}</div>
        </div>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="text-amber-200 text-sm underline">{{ __('judging.logout') }}</button>
        </form>
    </header>

    {{-- Entry list --}}
    <main class="max-w-lg mx-auto px-4 py-4 space-y-3">

        <template x-if="entries.length === 0">
            <p class="text-gray-500 text-center py-12">{{ __('judging.no_entries') }}</p>
        </template>

        <template x-for="entry in entries" :key="entry.id">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                {{-- Entry row header --}}
                <button
                    type="button"
                    class="w-full text-left px-4 py-4 flex items-center gap-3"
                    @click="toggleExpanded(entry.id)"
                >
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 truncate text-sm" x-text="entry.brewer_name"></div>
                        <div class="text-xs text-gray-400 truncate mt-0.5" x-text="entry.style_name"></div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        {{-- Score preview --}}
                        <span class="text-sm font-semibold tabular-nums text-green-700"
                            x-show="sheets[entry.id]?.status === 'submitted'"
                            x-text="sheetTotal(entry.id) + ' / 50'">
                        </span>
                        <span class="text-sm tabular-nums text-gray-400"
                            x-show="sheets[entry.id]?.status === 'draft' && sheetTotal(entry.id) > 0"
                            x-text="sheetTotal(entry.id) + ' / 50'">
                        </span>

                        {{-- Sync badge --}}
                        <span x-show="!sheets[entry.id] || sheets[entry.id].syncStatus === 'idle'" class="text-xs text-gray-300">—</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'syncing' || sheets[entry.id]?.syncStatus === 'pending'" class="text-xs text-blue-500 animate-pulse">{{ __('judging.saving') }}</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'synced' && sheets[entry.id]?.status === 'submitted'" class="text-xs text-green-600 font-medium">{{ __('judging.submitted') }}</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'synced' && sheets[entry.id]?.status === 'draft'" class="text-xs text-gray-400">{{ __('judging.saved') }}</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'error'" class="text-xs text-red-500 cursor-pointer" @click.stop="retryNow(entry.id)">{{ __('judging.retry') }}</span>

                        {{-- Expand chevron --}}
                        <svg class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0" :class="{ 'rotate-180': sheets[entry.id]?.expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                {{-- Scoresheet (expanded) --}}
                <div x-show="sheets[entry.id]?.expanded" x-transition class="border-t border-gray-100 px-4 pb-4 pt-3">

                    {{-- Submitted read-only view --}}
                    <template x-if="sheets[entry.id]?.status === 'submitted' && sheets[entry.id]?.syncStatus === 'synced'">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-green-600 font-semibold text-sm">{{ __('judging.submitted') }}</span>
                            </div>
                            <div class="text-sm text-gray-600 space-y-1 mb-3" aria-live="polite">
                                <div>{{ __('judging.section.aroma') }} <strong x-text="sheets[entry.id].aroma"></strong>/12
                                    · {{ __('judging.section.appearance') }} <strong x-text="sheets[entry.id].appearance"></strong>/3
                                    · {{ __('judging.section.flavour') }} <strong x-text="sheets[entry.id].flavour"></strong>/20</div>
                                <div>{{ __('judging.section.mouthfeel') }} <strong x-text="sheets[entry.id].mouthfeel"></strong>/5
                                    · {{ __('judging.section.overall') }} <strong x-text="sheets[entry.id].overall"></strong>/10</div>
                            </div>
                            <div class="mb-4">
                                <div class="text-base font-bold">{{ __('judging.total') }} <span x-text="sheetTotal(entry.id)"></span> / 50</div>
                                <div class="mt-0.5">
                                    <span class="text-sm font-semibold" x-text="bandInfo(sheetTotal(entry.id)).name"></span><span class="text-sm text-gray-500 ml-1.5" x-text="'(' + bandInfo(sheetTotal(entry.id)).range + ')'"></span>
                                    <div class="text-sm text-gray-700 italic" x-text="bandInfo(sheetTotal(entry.id)).description"></div>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="text-sm text-amber-700 underline"
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
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'aroma-' + entry.id" class="font-medium text-gray-800 text-sm">{{ __('judging.section.aroma') }}</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'aroma')" :aria-label="'View BJCP aroma guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700"><span x-text="sheets[entry.id].aroma"></span> / 12</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Aroma score, 0 to 12">
                                    @for ($n = 0; $n <= 12; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].aroma === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        @click="sheets[entry.id].aroma = {{ $n }}; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].aroma === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea :id="'aroma-notes-' + entry.id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="{{ __('judging.placeholder.aroma') }}" x-model="sheets[entry.id].aroma_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Aroma notes'"></textarea>
                            </div>

                            {{-- Appearance --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'appearance-' + entry.id" class="font-medium text-gray-800 text-sm">{{ __('judging.section.appearance') }}</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'appearance')" :aria-label="'View BJCP appearance guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700"><span x-text="sheets[entry.id].appearance"></span> / 3</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Appearance score, 0 to 3">
                                    @for ($n = 0; $n <= 3; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].appearance === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        @click="sheets[entry.id].appearance = {{ $n }}; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].appearance === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="{{ __('judging.placeholder.appearance') }}" x-model="sheets[entry.id].appearance_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Appearance notes'"></textarea>
                            </div>

                            {{-- Flavour --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'flavour-' + entry.id" class="font-medium text-gray-800 text-sm">{{ __('judging.section.flavour') }}</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'flavor')" :aria-label="'View BJCP flavour guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700"><span x-text="sheets[entry.id].flavour"></span> / 20</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Flavour score, 0 to 20">
                                    @for ($n = 0; $n <= 20; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].flavour === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        @click="sheets[entry.id].flavour = {{ $n }}; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].flavour === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="{{ __('judging.placeholder.flavour') }}" x-model="sheets[entry.id].flavour_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Flavour notes'"></textarea>
                            </div>

                            {{-- Mouthfeel --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'mouthfeel-' + entry.id" class="font-medium text-gray-800 text-sm">{{ __('judging.section.mouthfeel') }}</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'mouthfeel')" :aria-label="'View BJCP mouthfeel guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700"><span x-text="sheets[entry.id].mouthfeel"></span> / 5</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Mouthfeel score, 0 to 5">
                                    @for ($n = 0; $n <= 5; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].mouthfeel === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        @click="sheets[entry.id].mouthfeel = {{ $n }}; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].mouthfeel === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="{{ __('judging.placeholder.mouthfeel') }}" x-model="sheets[entry.id].mouthfeel_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Mouthfeel notes'"></textarea>
                            </div>

                            {{-- Overall --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'overall-' + entry.id" class="font-medium text-gray-800 text-sm">{{ __('judging.section.overall') }}</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'overall_impression')" :aria-label="'View BJCP overall impression guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <span class="text-sm tabular-nums font-semibold text-amber-700"><span x-text="sheets[entry.id].overall"></span> / 10</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-1.5 mb-2" role="group" aria-label="Overall Impression score, 0 to 10">
                                    @for ($n = 0; $n <= 10; $n++)
                                    <button type="button"
                                        class="w-9 h-9 rounded-lg text-sm font-semibold transition-colors"
                                        :class="sheets[entry.id].overall === {{ $n }} ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        @click="sheets[entry.id].overall = {{ $n }}; onInput(entry.id)"
                                        :aria-pressed="sheets[entry.id].overall === {{ $n }}"
                                    >{{ $n }}</button>
                                    @endfor
                                </div>
                                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="{{ __('judging.placeholder.overall') }}" x-model="sheets[entry.id].overall_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Overall impression notes'"></textarea>
                            </div>

                            {{-- Off-Flavor Descriptors --}}
                            <div x-data="{ descriptorsOpen: false }" class="mb-4">
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700"
                                    @click="descriptorsOpen = !descriptorsOpen"
                                >
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-90': descriptorsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    {{ __('judging.off_flavors') }}
                                    <template x-if="(sheets[entry.id]?.descriptors || []).length > 0">
                                        <span class="ml-1 bg-amber-100 text-amber-700 text-xs font-medium px-1.5 py-0.5 rounded-full" x-text="(sheets[entry.id]?.descriptors || []).length"></span>
                                    </template>
                                </button>
                                <div x-show="descriptorsOpen" x-transition class="mt-3 grid grid-cols-1 gap-y-2">
                                    @foreach($descriptors as $descriptor)
                                    <label class="flex items-start gap-2.5 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500 flex-shrink-0"
                                            :checked="(sheets[entry.id]?.descriptors || []).includes('{{ $descriptor['slug'] }}')"
                                            @change="toggleDescriptor(entry.id, '{{ $descriptor['slug'] }}')"
                                        >
                                        <span class="text-sm leading-snug">
                                            <span class="font-medium text-gray-800">{{ $descriptor['name'] }}</span>
                                            <span class="text-gray-500"> — {{ $descriptor['description'] }}</span>
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Total + Submit --}}
                            <div class="border-t border-gray-100 pt-3 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-lg font-bold text-gray-900">
                                        <span x-text="sheetTotal(entry.id)"></span> / 50
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold"
                                            :class="{
                                                'text-green-600': sheetTotal(entry.id) >= 38,
                                                'text-teal-600': sheetTotal(entry.id) >= 30 && sheetTotal(entry.id) < 38,
                                                'text-yellow-600': sheetTotal(entry.id) >= 21 && sheetTotal(entry.id) < 30,
                                                'text-red-600': sheetTotal(entry.id) < 21
                                            }"
                                            x-text="bandInfo(sheetTotal(entry.id)).name"></span><span class="text-xs text-gray-500 ml-1.5" x-text="'(' + bandInfo(sheetTotal(entry.id)).range + ')'"></span>
                                        <div class="text-xs text-gray-600 italic" x-text="bandInfo(sheetTotal(entry.id)).description"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3" aria-live="polite">
                                    <span x-show="sheets[entry.id]?.syncStatus === 'pending' || sheets[entry.id]?.syncStatus === 'syncing'" class="text-xs text-blue-500 animate-pulse">{{ __('judging.saving') }}</span>
                                    <span x-show="sheets[entry.id]?.syncStatus === 'synced'" class="text-xs text-gray-400">{{ __('judging.saved') }}</span>
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
        <div class="relative w-full max-w-lg mx-auto bg-white rounded-t-2xl shadow-xl px-5 pt-4 pb-8"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-0"
        >
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-600" x-text="styleGuide.section"></span>
                <button type="button" class="text-gray-400 hover:text-gray-600 text-xl leading-none" @click="closeGuide()">×</button>
            </div>
            <p class="text-sm text-gray-700 leading-relaxed" x-text="styleGuide.text"></p>
        </div>
    </div>

</body>
</html>
