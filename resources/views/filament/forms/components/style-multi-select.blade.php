@php
    $fieldWrapperView = $getFieldWrapperView();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();

    $categorized = $field->getCategorizedOptions();
    $totalCount = collect($categorized)->sum(fn ($opts) => count($opts));

    $categories = collect($categorized)
        ->map(fn ($opts, $name) => [
            'name'   => $name,
            'values' => array_keys($opts),
            'labels' => $opts,
        ])
        ->values()
        ->all();

    $allValues = collect($categorized)
        ->flatMap(fn ($opts) => array_keys($opts))
        ->map(fn ($v) => (string) $v)
        ->values()
        ->all();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        x-data="{
            search: '',
            activeIdx: 0,
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            categories: @js($categories),
            allValues: @js($allValues),

            visibleCategories() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.categories;
                return this.categories.filter(cat =>
                    cat.name.toLowerCase().includes(q) ||
                    Object.values(cat.labels).some(l => l.toLowerCase().includes(q))
                );
            },
            activeCategory() {
                const vis = this.visibleCategories();
                return vis[this.activeIdx] ?? null;
            },
            searchResults() {
                const q = this.search.trim().toLowerCase();
                if (!q) return [];
                return this.categories.flatMap(cat =>
                    Object.entries(cat.labels)
                        .filter(([, l]) => l.toLowerCase().includes(q))
                        .map(([value, label]) => ({ value, label, catName: cat.name }))
                );
            },
            onSearchChange() {
                this.activeIdx = 0;
            },
            countCat(cat) {
                const cur = new Set((this.state ?? []).map(String));
                return cat.values.filter(v => cur.has(String(v))).length;
            },
            isFull(cat)    { return this.countCat(cat) === cat.values.length; },
            isEmpty(cat)   { return this.countCat(cat) === 0; },
            isPartial(cat) { return !this.isEmpty(cat) && !this.isFull(cat); },
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
                const selected = new Set((this.state ?? []).map(String));
                return this.categories.flatMap(cat =>
                    Object.entries(cat.labels)
                        .filter(([v]) => selected.has(String(v)))
                        .map(([value, label]) => ({ value, label }))
                );
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

            {{-- Left: category list --}}
            <div class="w-1/4 shrink-0 overflow-y-auto border-r border-gray-200 dark:border-white/10">
                <template x-for="(cat, idx) in visibleCategories()" :key="cat.name">
                    <div
                        x-on:click="activeIdx = idx"
                        :title="cat.name"
                        class="group flex w-full cursor-pointer items-center justify-between border-l-2 py-[0.2rem] px-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5"
                        x-bind:class="{
                            'fi-sms-active border-l-primary-500 bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300': activeIdx === idx,
                            'border-l-transparent text-gray-700 dark:text-gray-200': activeIdx !== idx,
                        }"
                    >
                        <span
                            class="truncate text-sm group-hover:underline"
                            x-bind:class="activeIdx === idx ? 'font-semibold' : 'font-medium'"
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

                <div
                    x-show="visibleCategories().length === 0"
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
                                    <span class="block text-xs text-gray-400 dark:text-gray-500" x-text="item.catName"></span>
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
                            x-show="activeCategory() !== null"
                            class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-white/[0.03] px-4 py-[0.625rem] dark:border-white/10"
                        >
                            <span
                                class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100"
                                x-text="activeCategory()?.name"
                            ></span>

                            @unless ($isDisabled)
                                <div class="ml-2 flex shrink-0 items-center gap-1.5">
                                    <button
                                        type="button"
                                        x-on:click="selectCat(activeCategory())"
                                        class="cursor-pointer rounded border border-primary-300 bg-transparent px-2 py-0.5 text-xs font-medium text-primary-600 transition-colors hover:bg-primary-50"
                                    >Select all</button>
                                    <button
                                        type="button"
                                        x-on:click="deselectCat(activeCategory())"
                                        class="cursor-pointer rounded border border-gray-200 bg-transparent px-2 py-0.5 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-50"
                                    >Clear</button>
                                </div>
                            @endunless
                        </div>

                        {{-- Substyle list --}}
                        <div class="flex flex-1 flex-col overflow-y-auto">
                            <template x-for="[value, label] in Object.entries(activeCategory()?.labels ?? {})" :key="value">
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
                                x-show="activeCategory() === null"
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
