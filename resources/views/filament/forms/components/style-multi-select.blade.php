@php
    $fieldWrapperView = $getFieldWrapperView();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();

    $sourcedData = $field->getCategorizedOptions();

    $sources = collect($sourcedData)
        ->map(fn ($src, $key) => [
            'key'        => $key,
            'label'      => $src['label'],
            'categories' => collect($src['categories'])
                ->map(fn ($opts, $name) => [
                    'name'   => $name,
                    'values' => array_keys($opts),
                    'labels' => $opts,
                ])
                ->values()
                ->all(),
        ])
        ->values()
        ->all();

    $allValues = collect($sourcedData)
        ->flatMap(fn ($src) => collect($src['categories'])->flatMap(fn ($opts) => array_keys($opts)))
        ->map(fn ($v) => (string) $v)
        ->values()
        ->all();

    $totalCount = collect($sourcedData)
        ->sum(fn ($src) => collect($src['categories'])->sum(fn ($opts) => count($opts)));
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        x-data="{
            search: '',
            activeSource: null,
            activeCategory: null,
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            sources: @js($sources),
            allValues: @js($allValues),

            init() {
                if (this.sources.length > 0) {
                    this.activeSource = this.sources[0].key;
                    if (this.sources[0].categories.length > 0) {
                        this.activeCategory = this.sources[0].categories[0].name;
                    }
                }
            },
            getActiveSourceObj() {
                return this.sources.find(s => s.key === this.activeSource) ?? null;
            },
            getActiveCategoryObj() {
                const src = this.getActiveSourceObj();
                if (!src) return null;
                return src.categories.find(c => c.name === this.activeCategory) ?? null;
            },
            setActive(sourceKey, catName) {
                this.activeSource = sourceKey;
                this.activeCategory = catName;
            },
            visibleSources() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.sources;
                return this.sources
                    .map(src => ({
                        ...src,
                        categories: src.categories.filter(cat =>
                            cat.name.toLowerCase().includes(q) ||
                            Object.values(cat.labels).some(l => l.toLowerCase().includes(q))
                        )
                    }))
                    .filter(src => src.categories.length > 0);
            },
            searchResults() {
                const q = this.search.trim().toLowerCase();
                if (!q) return [];
                return this.sources.flatMap(src =>
                    src.categories.flatMap(cat =>
                        Object.entries(cat.labels)
                            .filter(([, l]) => l.toLowerCase().includes(q))
                            .map(([value, label]) => ({
                                value, label, catName: cat.name, sourceName: src.label,
                            }))
                    )
                );
            },
            onSearchChange() {
                const vis = this.visibleSources();
                if (vis.length > 0 && vis[0].categories.length > 0) {
                    this.activeSource = vis[0].key;
                    this.activeCategory = vis[0].categories[0].name;
                } else {
                    this.activeSource = null;
                    this.activeCategory = null;
                }
            },
            countCat(cat) {
                const cur = new Set((this.state ?? []).map(String));
                return cat.values.filter(v => cur.has(String(v))).length;
            },
            isFull(cat)    { return this.countCat(cat) === cat.values.length; },
            isEmpty(cat)   { return this.countCat(cat) === 0; },
            isPartial(cat) { return !this.isEmpty(cat) && !this.isFull(cat); },
            countSource(src) {
                return src.categories.reduce((n, cat) => n + this.countCat(cat), 0);
            },
            sourceTotalValues(src) {
                return src.categories.reduce((n, cat) => n + cat.values.length, 0);
            },
            isSourceFull(src)    { return this.countSource(src) === this.sourceTotalValues(src); },
            isSourceEmpty(src)   { return this.countSource(src) === 0; },
            isSourcePartial(src) { return !this.isSourceEmpty(src) && !this.isSourceFull(src); },
            selectSource(src) {
                const vals = src.categories.flatMap(cat => cat.values.map(String));
                this.state = [...new Set([...(this.state ?? []).map(String), ...vals])];
            },
            deselectSource(src) {
                const s = new Set(src.categories.flatMap(cat => cat.values.map(String)));
                this.state = (this.state ?? []).map(String).filter(v => !s.has(v));
            },
            toggleSource(src) {
                this.isSourceFull(src) ? this.deselectSource(src) : this.selectSource(src);
            },
            selectAll() {
                this.state = [...new Set([...(this.state ?? []).map(String), ...this.allValues.map(String)])];
            },
            deselectAll() {
                const s = new Set(this.allValues.map(String));
                this.state = (this.state ?? []).map(String).filter(v => !s.has(v));
            },
            selectCat(cat) {
                this.state = [...new Set([...(this.state ?? []).map(String), ...cat.values.map(String)])];
            },
            deselectCat(cat) {
                const s = new Set(cat.values.map(String));
                this.state = (this.state ?? []).map(String).filter(v => !s.has(v));
            },
            toggleCat(cat) {
                this.isFull(cat) ? this.deselectCat(cat) : this.selectCat(cat);
            },
            toggle(val) {
                val = String(val);
                const cur = (this.state ?? []).map(String);
                this.state = cur.includes(val) ? cur.filter(v => v !== val) : [...cur, val];
            },
            isChecked(val) {
                return (this.state ?? []).map(String).includes(String(val));
            },
            totalSelected() {
                return (this.state ?? []).length;
            },
            selectedItems() {
                const sel = new Set((this.state ?? []).map(String));
                const items = [];
                for (const src of this.sources) {
                    for (const cat of src.categories) {
                        for (const [value, label] of Object.entries(cat.labels)) {
                            if (sel.has(String(value))) {
                                items.push({ value: String(value), label });
                            }
                        }
                    }
                }
                return items;
            },
        }"
        class="fi-fo-checkbox-list"
    >
        {{-- Toolbar --}}
        <div class="sticky top-0 z-10 rounded-t-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">

            {{-- Pills row --}}
            @unless ($isDisabled)
                <div
                    x-show="totalSelected() > 0"
                    x-cloak
                    class="flex flex-wrap gap-1.5 max-h-20 overflow-y-auto p-3 border-b border-gray-100 dark:border-white/10"
                >
                    <span
                        x-show="totalSelected() === allValues.length"
                        class="inline-flex items-center gap-1 py-0.5 px-2.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700"
                    >
                        <span>All styles</span>
                        <button
                            type="button"
                            x-on:click="deselectAll()"
                            class="leading-none px-[3px] text-base opacity-60 transition-opacity hover:opacity-100"
                        >&times;</button>
                    </span>

                    <template x-for="item in (totalSelected() < allValues.length ? selectedItems() : [])" :key="item.value">
                        <span class="inline-flex items-center gap-1 py-0.5 px-2.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700">
                            <span x-text="item.label"></span>
                            <button
                                type="button"
                                x-on:click="toggle(item.value)"
                                class="leading-none px-[3px] text-base opacity-60 transition-opacity hover:opacity-100"
                            >&times;</button>
                        </span>
                    </template>
                </div>
            @endunless

            {{-- Search row --}}
            <div class="px-3 pt-3 pb-2">
                <x-filament::input.wrapper
                    inline-prefix
                    :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
                    class="w-full"
                >
                    <input
                        type="search"
                        placeholder="Search styles…"
                        x-model.debounce.200ms="search"
                        x-on:input="onSearchChange()"
                        class="fi-input fi-input-has-inline-prefix"
                    />
                </x-filament::input.wrapper>
            </div>

            {{-- Controls row --}}
            <div class="flex items-center gap-2 px-3 py-2">
                @unless ($isDisabled)
                    <x-filament::button type="button" color="gray" outlined size="sm" x-on:click="selectAll()">
                        Select all
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" outlined size="sm" x-on:click="deselectAll()">
                        Deselect all
                    </x-filament::button>
                @endunless
                <span class="ml-auto rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums whitespace-nowrap bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300">
                    <span x-text="totalSelected()"></span> / {{ $totalCount }}
                </span>
            </div>
        </div>

        {{-- Master-detail panels --}}
        <div class="flex h-[32rem] overflow-hidden rounded-b-lg border border-t-0 border-gray-200 dark:border-white/10">

            {{-- Left: source + category list --}}
            <div class="w-1/4 shrink-0 overflow-y-auto border-r border-gray-200 dark:border-white/10">
                <template x-for="src in visibleSources()" :key="src.key">
                    <div>
                        {{-- Source header row --}}
                        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-3 py-1.5">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="src.label"></span>
                            <button type="button"
                                    x-on:click.stop="toggleSource(src)"
                                    class="text-xs text-gray-400 hover:text-gray-600"
                                    x-text="countSource(src) + '/' + sourceTotalValues(src)"></button>
                        </div>

                        {{-- Category rows --}}
                        <template x-for="cat in src.categories" :key="src.key + '::' + cat.name">
                            <div
                                x-on:click="setActive(src.key, cat.name)"
                                :title="cat.name"
                                class="group flex w-full cursor-pointer items-center justify-between border-l-2 py-[0.2rem] px-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5"
                                x-bind:class="{
                                    'fi-sms-active border-l-primary-500 bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300': activeSource === src.key && activeCategory === cat.name,
                                    'border-l-transparent text-gray-700 dark:text-gray-200': !(activeSource === src.key && activeCategory === cat.name),
                                }"
                            >
                                <span
                                    class="truncate text-sm group-hover:underline"
                                    x-bind:class="activeSource === src.key && activeCategory === cat.name ? 'font-semibold' : 'font-medium'"
                                    x-text="cat.name"
                                ></span>

                                @unless ($isDisabled)
                                    <button
                                        type="button"
                                        x-on:click.stop="toggleCat(cat)"
                                        :title="isFull(cat) ? 'Deselect all' : 'Select all'"
                                        class="ml-2 shrink-0 rounded-full px-1.5 py-0.5 text-xs tabular-nums font-semibold transition-opacity hover:opacity-70"
                                        x-bind:class="{
                                            'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300': isFull(cat),
                                            'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300': isPartial(cat),
                                            'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400': isEmpty(cat),
                                        }"
                                        x-text="countCat(cat) + '/' + cat.values.length"
                                    ></button>
                                @else
                                    <span
                                        class="ml-2 shrink-0 rounded-full px-1.5 py-0.5 text-xs tabular-nums font-semibold"
                                        x-bind:class="{
                                            'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300': isFull(cat),
                                            'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300': isPartial(cat),
                                            'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400': isEmpty(cat),
                                        }"
                                        x-text="countCat(cat) + '/' + cat.values.length"
                                    ></span>
                                @endunless
                            </div>
                        </template>
                    </div>
                </template>

                <div
                    x-show="visibleSources().length === 0"
                    class="px-3 py-4 text-center text-sm text-gray-400 dark:text-gray-500"
                >
                    No categories match.
                </div>
            </div>

            {{-- Right: content panel --}}
            <div class="flex flex-1 flex-col overflow-hidden">

                {{-- Search results: flat cross-category list --}}
                <template x-if="search.trim() !== ''">
                    <div class="flex flex-1 flex-col overflow-y-auto">
                        <template x-for="item in searchResults()" :key="item.value">
                            <label
                                class="flex cursor-pointer select-none items-center gap-3 px-4 py-[0.625rem] border-b border-white/[0.04] hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                x-bind:class="isChecked(item.value) ? 'dark:bg-primary-500/5' : ''"
                            >
                                <input
                                    type="checkbox"
                                    x-bind:value="item.value"
                                    x-bind:checked="isChecked(item.value)"
                                    x-on:change="toggle(item.value)"
                                    {{ $isDisabled ? 'disabled' : '' }}
                                    class="fi-checkbox-input"
                                />
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm text-gray-700 dark:text-gray-200" x-text="item.label"></span>
                                    <span class="block text-xs text-gray-400 dark:text-gray-500" x-text="item.catName + ' · ' + item.sourceName"></span>
                                </span>
                            </label>
                        </template>

                        <div
                            x-show="searchResults().length === 0"
                            class="px-4 py-6 text-center text-sm text-gray-400 dark:text-gray-500"
                        >
                            No styles match your search.
                        </div>
                    </div>
                </template>

                {{-- Normal view: category header + substyle checkboxes --}}
                <template x-if="search.trim() === ''">
                    <div class="flex flex-1 flex-col overflow-hidden">

                        {{-- Header --}}
                        <div
                            x-show="getActiveCategoryObj() !== null"
                            class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-white/[0.03] px-4 py-[0.625rem] dark:border-white/10"
                        >
                            <span
                                class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100"
                                x-text="getActiveCategoryObj()?.name"
                            ></span>

                            @unless ($isDisabled)
                                <div class="ml-2 flex shrink-0 items-center gap-1.5">
                                    <button
                                        type="button"
                                        x-on:click="selectCat(getActiveCategoryObj())"
                                        class="cursor-pointer rounded border border-primary-300 bg-transparent px-2 py-0.5 text-xs font-medium text-primary-600 transition-colors hover:bg-primary-50"
                                    >Select all</button>
                                    <button
                                        type="button"
                                        x-on:click="deselectCat(getActiveCategoryObj())"
                                        class="cursor-pointer rounded border border-gray-200 bg-transparent px-2 py-0.5 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-50"
                                    >Clear</button>
                                </div>
                            @endunless
                        </div>

                        {{-- Substyle list --}}
                        <div class="flex flex-1 flex-col overflow-y-auto">
                            <template x-for="[value, label] in Object.entries(getActiveCategoryObj()?.labels ?? {})" :key="value">
                                <label
                                    class="flex cursor-pointer select-none items-center gap-3 px-4 py-[0.625rem] border-b border-gray-100 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                    x-bind:class="isChecked(value) ? 'dark:bg-primary-500/5' : ''"
                                >
                                    <input
                                        type="checkbox"
                                        x-bind:value="value"
                                        x-bind:checked="isChecked(value)"
                                        x-on:change="toggle(value)"
                                        {{ $isDisabled ? 'disabled' : '' }}
                                        class="fi-checkbox-input"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-200" x-text="label"></span>
                                </label>
                            </template>

                            <div
                                x-show="getActiveCategoryObj() === null"
                                class="px-4 py-6 text-center text-sm text-gray-400 dark:text-gray-500"
                            >
                                No styles available.
                            </div>
                        </div>

                    </div>
                </template>

            </div>
        </div>

    </div>
</x-dynamic-component>
