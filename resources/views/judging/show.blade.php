<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Judging · {{ $competition->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/judging.js'])
</head>
<body class="bg-gray-50 min-h-screen" x-data="judgingApp(serverData)" x-init="init()">

    {{-- Header --}}
    <header class="bg-amber-700 text-white px-4 py-3 flex justify-between items-center sticky top-0 z-10 shadow">
        <div>
            <div class="font-bold text-lg leading-tight">{{ $competition->name }}</div>
            <div class="text-amber-200 text-sm">Hi, {{ $judge->name }}</div>
        </div>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="text-amber-200 text-sm underline">Log out</button>
        </form>
    </header>

    {{-- Server data --}}
    <script>
        const serverData = {!! $serverDataJson !!};
    </script>

    {{-- Entry list --}}
    <main class="max-w-lg mx-auto px-4 py-4 space-y-3">

        <template x-if="entries.length === 0">
            <p class="text-gray-500 text-center py-12">No entries to judge. Either all entries are yours, or none have been registered.</p>
        </template>

        <template x-for="entry in entries" :key="entry.id">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                {{-- Entry row header --}}
                <button
                    type="button"
                    class="w-full text-left px-4 py-3 flex justify-between items-center gap-3"
                    @click="toggleExpanded(entry.id)"
                >
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900 truncate">
                            #<span x-text="entry.bottle_number"></span>
                            · <span x-text="entry.style_code"></span>
                            · "<span x-text="entry.name"></span>"
                        </div>
                        <div class="text-xs text-gray-500 truncate" x-text="entry.style_name"></div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        {{-- Score preview --}}
                        <span class="text-sm font-medium text-gray-700"
                            x-show="sheets[entry.id] && sheets[entry.id].status === 'submitted'"
                            x-text="sheetTotal(entry.id) + ' / 50'">
                        </span>
                        <span class="text-sm text-gray-400"
                            x-show="sheets[entry.id] && sheets[entry.id].status === 'draft' && sheetTotal(entry.id) > 0"
                            x-text="sheetTotal(entry.id) + ' / 50'">
                        </span>

                        {{-- Sync badge --}}
                        <span x-show="!sheets[entry.id] || sheets[entry.id].syncStatus === 'idle'" class="text-xs text-gray-400">—</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'syncing' || sheets[entry.id]?.syncStatus === 'pending'" class="text-xs text-blue-500 animate-pulse">Saving…</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'synced' && sheets[entry.id]?.status === 'submitted'" class="text-xs text-green-600 font-medium">Submitted ✓</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'synced' && sheets[entry.id]?.status === 'draft'" class="text-xs text-gray-400">Saved ✓</span>
                        <span x-show="sheets[entry.id]?.syncStatus === 'error'" class="text-xs text-red-500 cursor-pointer" @click.stop="retryNow(entry.id)">⚠ Retry</span>

                        {{-- Expand chevron --}}
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': sheets[entry.id]?.expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <span class="text-green-600 font-semibold text-sm">✓ Submitted</span>
                            </div>
                            <div class="text-sm text-gray-600 space-y-1 mb-3" aria-live="polite">
                                <div>Aroma <strong x-text="sheets[entry.id].aroma"></strong>/12
                                    · Appearance <strong x-text="sheets[entry.id].appearance"></strong>/3
                                    · Flavour <strong x-text="sheets[entry.id].flavour"></strong>/20</div>
                                <div>Mouthfeel <strong x-text="sheets[entry.id].mouthfeel"></strong>/5
                                    · Overall <strong x-text="sheets[entry.id].overall"></strong>/10</div>
                            </div>
                            <div class="text-base font-bold mb-4">
                                Total: <span x-text="sheetTotal(entry.id)"></span> / 50
                                · <span x-text="bandLabel(sheetTotal(entry.id))"></span>
                            </div>
                            <button
                                type="button"
                                class="text-sm text-amber-700 underline"
                                @click="reopenSheet(entry.id)"
                            >Reopen to edit</button>
                        </div>
                    </template>

                    {{-- Editable scoresheet --}}
                    <template x-if="!sheets[entry.id] || sheets[entry.id]?.status !== 'submitted' || sheets[entry.id]?.syncStatus !== 'synced'">
                        <div>
                            {{-- Aroma --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'aroma-' + entry.id" class="font-medium text-gray-800 text-sm">Aroma</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'aroma')" :aria-label="'View BJCP aroma guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <div class="flex items-center gap-1" role="group" :aria-label="'Aroma score, 0 to 12'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].aroma = Math.max(0, sheets[entry.id].aroma - 1); onInput(entry.id)">−</button>
                                        <input :id="'aroma-' + entry.id" type="number" min="0" max="12" class="w-12 h-9 text-center border border-gray-300 rounded-lg text-sm font-bold" x-model.number="sheets[entry.id].aroma" @change="onInput(entry.id)" :aria-label="'Aroma score, 0 to 12'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].aroma = Math.min(12, sheets[entry.id].aroma + 1); onInput(entry.id)">+</button>
                                        <span class="text-xs text-gray-400 ml-1">/12</span>
                                    </div>
                                </div>
                                <textarea :id="'aroma-notes-' + entry.id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="Aroma notes…" x-model="sheets[entry.id].aroma_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Aroma notes'"></textarea>
                            </div>

                            {{-- Appearance --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'appearance-' + entry.id" class="font-medium text-gray-800 text-sm">Appearance</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'appearance')" :aria-label="'View BJCP appearance guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <div class="flex items-center gap-1" role="group" :aria-label="'Appearance score, 0 to 3'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].appearance = Math.max(0, sheets[entry.id].appearance - 1); onInput(entry.id)">−</button>
                                        <input :id="'appearance-' + entry.id" type="number" min="0" max="3" class="w-12 h-9 text-center border border-gray-300 rounded-lg text-sm font-bold" x-model.number="sheets[entry.id].appearance" @change="onInput(entry.id)" :aria-label="'Appearance score, 0 to 3'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].appearance = Math.min(3, sheets[entry.id].appearance + 1); onInput(entry.id)">+</button>
                                        <span class="text-xs text-gray-400 ml-1">/3</span>
                                    </div>
                                </div>
                                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="Appearance notes…" x-model="sheets[entry.id].appearance_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Appearance notes'"></textarea>
                            </div>

                            {{-- Flavour --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'flavour-' + entry.id" class="font-medium text-gray-800 text-sm">Flavour</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'flavor')" :aria-label="'View BJCP flavour guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <div class="flex items-center gap-1" role="group" :aria-label="'Flavour score, 0 to 20'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].flavour = Math.max(0, sheets[entry.id].flavour - 1); onInput(entry.id)">−</button>
                                        <input :id="'flavour-' + entry.id" type="number" min="0" max="20" class="w-12 h-9 text-center border border-gray-300 rounded-lg text-sm font-bold" x-model.number="sheets[entry.id].flavour" @change="onInput(entry.id)" :aria-label="'Flavour score, 0 to 20'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].flavour = Math.min(20, sheets[entry.id].flavour + 1); onInput(entry.id)">+</button>
                                        <span class="text-xs text-gray-400 ml-1">/20</span>
                                    </div>
                                </div>
                                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="Flavour notes…" x-model="sheets[entry.id].flavour_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Flavour notes'"></textarea>
                            </div>

                            {{-- Mouthfeel --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'mouthfeel-' + entry.id" class="font-medium text-gray-800 text-sm">Mouthfeel</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'mouthfeel')" :aria-label="'View BJCP mouthfeel guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <div class="flex items-center gap-1" role="group" :aria-label="'Mouthfeel score, 0 to 5'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].mouthfeel = Math.max(0, sheets[entry.id].mouthfeel - 1); onInput(entry.id)">−</button>
                                        <input :id="'mouthfeel-' + entry.id" type="number" min="0" max="5" class="w-12 h-9 text-center border border-gray-300 rounded-lg text-sm font-bold" x-model.number="sheets[entry.id].mouthfeel" @change="onInput(entry.id)" :aria-label="'Mouthfeel score, 0 to 5'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].mouthfeel = Math.min(5, sheets[entry.id].mouthfeel + 1); onInput(entry.id)">+</button>
                                        <span class="text-xs text-gray-400 ml-1">/5</span>
                                    </div>
                                </div>
                                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="Mouthfeel notes…" x-model="sheets[entry.id].mouthfeel_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Mouthfeel notes'"></textarea>
                            </div>

                            {{-- Overall --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label :for="'overall-' + entry.id" class="font-medium text-gray-800 text-sm">Overall Impression</label>
                                        <button type="button" class="w-4 h-4 rounded-full border border-amber-300 text-amber-600 text-[10px] font-bold leading-none flex items-center justify-center hover:bg-amber-50 flex-shrink-0" @click.stop="openGuide(entry.id, 'overall_impression')" :aria-label="'View BJCP overall impression guideline for ' + entry.style_name">i</button>
                                    </div>
                                    <div class="flex items-center gap-1" role="group" :aria-label="'Overall impression score, 0 to 10'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].overall = Math.max(0, sheets[entry.id].overall - 1); onInput(entry.id)">−</button>
                                        <input :id="'overall-' + entry.id" type="number" min="0" max="10" class="w-12 h-9 text-center border border-gray-300 rounded-lg text-sm font-bold" x-model.number="sheets[entry.id].overall" @change="onInput(entry.id)" :aria-label="'Overall impression score, 0 to 10'">
                                        <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-700 font-bold text-lg flex items-center justify-center" @click="sheets[entry.id].overall = Math.min(10, sheets[entry.id].overall + 1); onInput(entry.id)">+</button>
                                        <span class="text-xs text-gray-400 ml-1">/10</span>
                                    </div>
                                </div>
                                <textarea class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 resize-none" rows="2" placeholder="Overall impression notes…" x-model="sheets[entry.id].overall_notes" @input.debounce.500ms="onInput(entry.id)" :aria-label="'Overall impression notes'"></textarea>
                            </div>

                            {{-- Total + Submit --}}
                            <div class="border-t border-gray-100 pt-3 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-lg font-bold text-gray-900">
                                        <span x-text="sheetTotal(entry.id)"></span> / 50
                                    </div>
                                    <div class="text-xs font-medium"
                                        :class="{
                                            'text-green-600': sheetTotal(entry.id) >= 38,
                                            'text-teal-600': sheetTotal(entry.id) >= 30 && sheetTotal(entry.id) < 38,
                                            'text-yellow-600': sheetTotal(entry.id) >= 21 && sheetTotal(entry.id) < 30,
                                            'text-red-600': sheetTotal(entry.id) < 21
                                        }"
                                        x-text="bandLabel(sheetTotal(entry.id))">
                                    </div>
                                </div>
                                <div class="flex items-center gap-3" aria-live="polite">
                                    <span x-show="sheets[entry.id]?.syncStatus === 'pending' || sheets[entry.id]?.syncStatus === 'syncing'" class="text-xs text-blue-500 animate-pulse">Saving…</span>
                                    <span x-show="sheets[entry.id]?.syncStatus === 'synced'" class="text-xs text-gray-400">Saved ✓</span>
                                    <span x-show="sheets[entry.id]?.syncStatus === 'error'" class="text-xs text-red-500 cursor-pointer" @click="retryNow(entry.id)">⚠ Retry</span>
                                    <button
                                        type="button"
                                        class="bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700 active:scale-95 transition-transform"
                                        @click="submitSheet(entry.id)"
                                    >Submit ▶</button>
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
