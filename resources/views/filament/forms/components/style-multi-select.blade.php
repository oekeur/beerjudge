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

<style>
    .fi-sms-cat-row:hover .fi-sms-cat-name { text-decoration: underline; }
    .fi-sms-cat-row.fi-sms-active .fi-sms-cat-name { font-weight: 600; }
</style>

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
        <div class="sticky top-0 z-10 rounded-t-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900" style="z-index:10">

            {{-- Pills row --}}
            @unless ($isDisabled)
                <div
                    x-show="totalSelected() > 0"
                    x-cloak
                    style="display:flex;flex-wrap:wrap;gap:6px;max-height:5rem;overflow-y:auto;padding:0.75rem 0.75rem;border-bottom:1px solid #f3f4f6"
                >
                    <template x-for="item in selectedItems()" :key="item.value">
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:500;background:var(--color-primary-100,#fef3c7);color:var(--color-primary-700,#b45309)">
                            <span x-text="item.label"></span>
                            <button
                                type="button"
                                x-on:click="toggle(item.value)"
                                style="line-height:1;padding:0 3px;font-size:1rem;opacity:0.6"
                                onmouseover="this.style.opacity='1'"
                                onmouseout="this.style.opacity='0.6'"
                            >&times;</button>
                        </span>
                    </template>
                </div>
            @endunless

            {{-- Search row --}}
            <div style="padding:0.75rem 0.75rem 0.5rem">
                <x-filament::input.wrapper
                    inline-prefix
                    :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
                    style="width:100%"
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
            <div style="display:flex;align-items:center;padding:0.5rem 0.75rem;gap:0.5rem">
                @unless ($isDisabled)
                    <x-filament::button type="button" color="gray" outlined size="sm" x-on:click="selectAll()">
                        Select all
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" outlined size="sm" x-on:click="deselectAll()">
                        Deselect all
                    </x-filament::button>
                @endunless
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums whitespace-nowrap bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300" style="margin-left:auto">
                    <span x-text="totalSelected()"></span> / {{ $totalCount }}
                </span>
            </div>
        </div>

        {{-- Master-detail panels --}}
        <div class="rounded-b-lg border border-t-0 border-gray-200 dark:border-white/10" style="display:flex;height:32rem;overflow:hidden">

            {{-- Left: category list --}}
            <div class="border-r border-gray-200 dark:border-white/10" style="width:25%;flex-shrink:0;overflow-y:auto">
                <template x-for="(cat, idx) in visibleCategories()" :key="cat.name">
                    <div
                        x-on:click="activeIdx = idx"
                        :title="cat.name"
                        class="fi-sms-cat-row flex w-full cursor-pointer items-center justify-between px-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5"
                        x-bind:class="{
                            'fi-sms-active bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300': activeIdx === idx,
                            'text-gray-700 dark:text-gray-200': activeIdx !== idx,
                        }"
                        x-bind:style="{
                            'border-left': activeIdx === idx ? '2px solid var(--color-primary-500)' : '2px solid transparent',
                            'padding-top': '0.875rem',
                            'padding-bottom': '0.875rem',
                        }"
                    >
                        <span class="fi-sms-cat-name truncate text-sm font-medium" x-text="cat.name"></span>

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
                                style="padding-top:1rem;padding-bottom:1rem;border-bottom:1px solid rgba(255,255,255,0.04)"
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
                            style="padding-top:1rem;padding-bottom:1rem;flex-shrink:0;background:rgba(255,255,255,0.03)"
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
                                        style="border-radius:4px;border:1px solid var(--color-primary-300,#fcd34d);padding:2px 8px;font-size:0.75rem;font-weight:500;color:var(--color-primary-600,#d97706);cursor:pointer;transition:background 0.1s;background:transparent"
                                        onmouseover="this.style.background='var(--color-primary-50,#fffbeb)'"
                                        onmouseout="this.style.background='transparent'"
                                    >Select all</button>
                                    <button
                                        type="button"
                                        x-on:click="deselectCat(activeCategory())"
                                        style="border-radius:4px;border:1px solid #e5e7eb;padding:2px 8px;font-size:0.75rem;font-weight:500;color:#6b7280;cursor:pointer;transition:background 0.1s;background:transparent"
                                        onmouseover="this.style.background='#f9fafb'"
                                        onmouseout="this.style.background='transparent'"
                                    >Clear</button>
                                </div>
                            @endunless
                        </div>

                        {{-- Substyle list --}}
                        <div style="flex:1;display:flex;flex-direction:column;overflow-y:auto">
                            <template x-for="[value, label] in Object.entries(activeCategory()?.labels ?? {})" :key="value">
                                <label
                                    class="flex cursor-pointer select-none items-center gap-3 px-4 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                    style="padding-top:1rem;padding-bottom:1rem;border-bottom:1px solid rgba(255,255,255,0.04)"
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
