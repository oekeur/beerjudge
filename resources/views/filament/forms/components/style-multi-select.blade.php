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
        <div class="sticky top-0 z-10 flex items-center gap-2 rounded-t-lg border border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-900">
            <x-filament::input.wrapper
                inline-prefix
                :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
                style="flex:1;min-width:10rem"
            >
                <input
                    type="search"
                    placeholder="Search styles…"
                    x-model.debounce.200ms="search"
                    x-on:input="onSearchChange()"
                    class="fi-input fi-input-has-inline-prefix"
                />
            </x-filament::input.wrapper>

            @unless ($isDisabled)
                <x-filament::button type="button" color="gray" outlined size="sm" x-on:click="selectAll()">
                    Select all
                </x-filament::button>
                <x-filament::button type="button" color="gray" outlined size="sm" x-on:click="deselectAll()">
                    Deselect all
                </x-filament::button>
            @endunless

            <span class="rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums whitespace-nowrap bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300">
                <span x-text="totalSelected()"></span> / {{ $totalCount }}
            </span>
        </div>

        {{-- Selected pills --}}
        @unless ($isDisabled)
            <div
                x-show="totalSelected() > 0"
                x-cloak
                class="flex flex-wrap gap-1.5 border-l border-r border-b border-gray-200 bg-gray-50 px-3 py-2 dark:border-white/10 dark:bg-white/5"
                style="max-height:5rem;overflow-y:auto"
            >
                <template x-for="item in selectedItems()" :key="item.value">
                    <span class="flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
                        <span x-text="item.label"></span>
                        <button
                            type="button"
                            x-on:click="toggle(item.value)"
                            class="rounded-full hover:bg-primary-200 dark:hover:bg-primary-500/30 transition-colors"
                            style="line-height:1;padding:1px 3px;font-size:0.875rem"
                        >&times;</button>
                    </span>
                </template>
            </div>
        @endunless

        {{-- Master-detail panels --}}
        <div class="rounded-b-lg border border-t-0 border-gray-200 dark:border-white/10" style="display:flex;height:32rem;overflow:hidden">

            {{-- Left: category list --}}
            <div class="border-r border-gray-200 dark:border-white/10" style="width:40%;flex-shrink:0;overflow-y:auto">
                <template x-for="(cat, idx) in visibleCategories()" :key="cat.name">
                    <div
                        x-on:click="activeIdx = idx"
                        :title="cat.name"
                        class="flex w-full cursor-pointer items-center justify-between px-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5"
                        style="padding-top:0.625rem;padding-bottom:0.625rem"
                        x-bind:class="activeIdx === idx
                            ? 'bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300'
                            : 'text-gray-700 dark:text-gray-200'"
                        x-bind:style="activeIdx === idx ? 'border-left:2px solid var(--color-primary-500)' : 'border-left:2px solid transparent'"
                    >
                        <span class="truncate text-sm font-medium" x-text="cat.name"></span>

                        @unless ($isDisabled)
                            <button
                                type="button"
                                x-on:click.stop="toggleCat(cat)"
                                :title="isFull(cat) ? 'Deselect all' : 'Select all'"
                                class="rounded-full px-1.5 py-0.5 text-xs tabular-nums font-semibold transition-opacity hover:opacity-70"
                                style="margin-left:0.5rem;flex-shrink:0"
                                x-bind:class="{
                                    'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300': isFull(cat),
                                    'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300': isPartial(cat),
                                    'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400': isEmpty(cat),
                                }"
                                x-text="countCat(cat) + '/' + cat.values.length"
                            ></button>
                        @else
                            <span
                                class="rounded-full px-1.5 py-0.5 text-xs tabular-nums font-semibold"
                                style="margin-left:0.5rem;flex-shrink:0"
                                x-bind:class="{
                                    'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300': isFull(cat),
                                    'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300': isPartial(cat),
                                    'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400': isEmpty(cat),
                                }"
                                x-text="countCat(cat) + '/' + cat.values.length"
                            ></span>
                        @endunless

                        <svg
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            class="text-gray-400 dark:text-gray-500"
                            style="width:12px;height:12px;margin-left:4px;flex-shrink:0;transition:opacity 0.15s"
                            x-bind:style="activeIdx === idx ? 'width:12px;height:12px;margin-left:4px;flex-shrink:0;opacity:0.8' : 'width:12px;height:12px;margin-left:4px;flex-shrink:0;opacity:0.3'"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
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
            <div style="flex:1;display:flex;flex-direction:column;overflow:hidden">

                {{-- Search results: flat cross-category list --}}
                <template x-if="search.trim() !== ''">
                    <div style="flex:1;display:flex;flex-direction:column;overflow-y:auto">
                        <template x-for="item in searchResults()" :key="item.value">
                            <label
                                class="flex cursor-pointer select-none items-center gap-3 px-4 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                style="padding-top:0.625rem;padding-bottom:0.625rem;border-bottom:1px solid rgba(255,255,255,0.04)"
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
                                <span style="flex:1;min-width:0">
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
                    <div style="flex:1;display:flex;flex-direction:column;overflow:hidden">

                        {{-- Header --}}
                        <div
                            x-show="activeCategory() !== null"
                            class="flex items-center justify-between border-b border-gray-200 dark:border-white/10 px-4"
                            style="padding-top:0.625rem;padding-bottom:0.625rem;flex-shrink:0;background:rgba(255,255,255,0.03)"
                        >
                            <span
                                class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100"
                                x-text="activeCategory()?.name"
                            ></span>

                            @unless ($isDisabled)
                                <div class="flex items-center gap-1.5" style="margin-left:0.5rem;flex-shrink:0">
                                    <button
                                        type="button"
                                        x-on:click="selectCat(activeCategory())"
                                        class="rounded border border-primary-300 px-2 py-0.5 text-xs font-medium text-primary-600 hover:bg-primary-50 transition-colors dark:border-primary-600 dark:text-primary-400 dark:hover:bg-primary-500/10"
                                    >Select all</button>
                                    <button
                                        type="button"
                                        x-on:click="deselectCat(activeCategory())"
                                        class="rounded border border-gray-200 px-2 py-0.5 text-xs font-medium text-gray-500 hover:bg-gray-50 transition-colors dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                                    >Clear</button>
                                </div>
                            @endunless
                        </div>

                        {{-- Substyle list --}}
                        <div style="flex:1;display:flex;flex-direction:column;overflow-y:auto">
                            <template x-for="[value, label] in Object.entries(activeCategory()?.labels ?? {})" :key="value">
                                <label
                                    class="flex cursor-pointer select-none items-center gap-3 px-4 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                    style="padding-top:0.625rem;padding-bottom:0.625rem;border-bottom:1px solid rgba(255,255,255,0.04)"
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
