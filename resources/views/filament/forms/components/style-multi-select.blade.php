@php
    use Illuminate\Support\Str;

    $fieldWrapperView = $getFieldWrapperView();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();

    $categorized = $field->getCategorizedOptions();
    $totalCount = collect($categorized)->sum(fn ($opts) => count($opts));
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        x-data="{
            search: '',
            openGroups: {},
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            toggleGroup(cat) {
                this.openGroups[cat] = !this.openGroups[cat];
            },
            isOpen(cat) {
                if (this.search.trim()) return true;
                return !!this.openGroups[cat];
            },
            allValues: @js(collect($categorized)->flatMap(fn ($opts) => array_keys($opts))->map(fn ($v) => (string) $v)->values()->all()),
            selectAll() {
                const cur = Array.isArray(this.state) ? this.state.map(String) : [];
                this.allValues.forEach(v => { if (!cur.includes(v)) cur.push(v); });
                this.state = cur;
            },
            deselectAll() {
                const all = new Set(this.allValues);
                this.state = (Array.isArray(this.state) ? this.state.map(String) : []).filter(v => !all.has(v));
            },
            selectCat(values) {
                const cur = Array.isArray(this.state) ? [...this.state.map(String)] : [];
                values.map(String).forEach(v => { if (!cur.includes(v)) cur.push(v); });
                this.state = cur;
            },
            deselectCat(values) {
                const rem = new Set(values.map(String));
                this.state = (Array.isArray(this.state) ? this.state.map(String) : []).filter(v => !rem.has(v));
            },
            countCat(values) {
                const cur = Array.isArray(this.state) ? this.state.map(String) : [];
                return values.map(String).filter(v => cur.includes(v)).length;
            },
            toggle(val) {
                val = String(val);
                const cur = Array.isArray(this.state) ? this.state.map(String) : [];
                this.state = cur.includes(val) ? cur.filter(v => v !== val) : [...cur, val];
            },
            isChecked(val) {
                return Array.isArray(this.state) && this.state.map(String).includes(String(val));
            },
        }"
        class="fi-fo-checkbox-list space-y-3"
    >
        @unless ($isDisabled)
            <div class="flex gap-2 items-center">
                <x-filament::input.wrapper
                    inline-prefix
                    :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
                    class="flex-1"
                >
                    <input
                        type="search"
                        placeholder="Search styles…"
                        x-model.debounce.300ms="search"
                        class="fi-input fi-input-has-inline-prefix"
                    />
                </x-filament::input.wrapper>

                <x-filament::button type="button" color="gray" outlined size="sm" x-on:click="selectAll()">
                    Select all
                </x-filament::button>

                <x-filament::button type="button" color="gray" outlined size="sm" x-on:click="deselectAll()">
                    Deselect all
                </x-filament::button>
            </div>
        @endunless

        <p class="text-sm text-gray-500 dark:text-gray-400">
            <span x-text="Array.isArray(state) ? state.length : 0"></span> of {{ $totalCount }} selected
        </p>

        <div class="rounded-lg border border-gray-200 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/5 overflow-hidden">
            @forelse ($categorized as $categoryName => $categoryOptions)
                @php
                    $catValues = array_keys($categoryOptions);
                    $catLabels = collect($categoryOptions)->map(fn ($l) => Str::lower($l))->values()->all();
                @endphp

                <div x-show="!search.trim() || @js($catLabels).some(l => l.includes(search.toLowerCase().trim()))">

                    {{-- Category header --}}
                    <div class="flex items-center bg-gray-50 dark:bg-white/5">
                        <button
                            type="button"
                            x-on:click="toggleGroup(@js($categoryName))"
                            class="flex flex-1 items-center justify-between px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-white/10 transition-colors min-w-0"
                        >
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide truncate">{{ $categoryName }}</span>
                            <span class="flex items-center gap-2 ml-2 shrink-0">
                                <span
                                    class="text-xs text-gray-400 dark:text-gray-500 tabular-nums"
                                    x-text="countCat(@js($catValues)) + '/{{ count($categoryOptions) }}'"
                                ></span>
                                <svg
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    style="width:14px;height:14px;transition:transform 0.15s;flex-shrink:0"
                                    x-bind:style="'width:14px;height:14px;flex-shrink:0;transition:transform 0.15s;transform:rotate(' + (isOpen(@js($categoryName)) ? '180' : '0') + 'deg)'"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>

                        @unless ($isDisabled)
                            <div class="flex gap-1 px-2 shrink-0">
                                <button
                                    type="button"
                                    x-on:click.stop="selectCat(@js($catValues))"
                                    class="text-xs text-primary-600 dark:text-primary-400 hover:underline px-1"
                                >all</button>
                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                <button
                                    type="button"
                                    x-on:click.stop="deselectCat(@js($catValues))"
                                    class="text-xs text-gray-400 dark:text-gray-500 hover:underline px-1"
                                >none</button>
                            </div>
                        @endunless
                    </div>

                    {{-- Category options --}}
                    <div
                        x-show="isOpen(@js($categoryName))"
                        class="divide-y divide-gray-50 dark:divide-white/5"
                    >
                        @foreach ($categoryOptions as $value => $label)
                            <div
                                x-show="!search.trim() || @js(Str::lower($label)).includes(search.toLowerCase().trim())"
                                class="fi-fo-checkbox-list-option-ctn px-3 py-2"
                            >
                                <label class="fi-fo-checkbox-list-option flex items-center gap-2.5 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        value="{{ $value }}"
                                        x-bind:checked="isChecked('{{ $value }}')"
                                        x-on:change="toggle('{{ $value }}')"
                                        {{ $isDisabled ? 'disabled' : '' }}
                                        class="fi-checkbox-input"
                                    />
                                    <span class="fi-fo-checkbox-list-option-label text-sm text-gray-700 dark:text-gray-200">
                                        {{ $label }}
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                </div>
            @empty
                <div class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                    No styles available.
                </div>
            @endforelse

        </div>

    </div>
</x-dynamic-component>
