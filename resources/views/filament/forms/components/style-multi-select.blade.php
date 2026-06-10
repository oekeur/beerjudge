@php
    $fieldWrapperView = $getFieldWrapperView();
    $extraInputAttributeBag = $getExtraInputAttributeBag();
    $isHtmlAllowed = $isHtmlAllowed();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
    $options = $getOptions();
    $livewireKey = $getLivewireKey();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');

    $optionsForJs = collect($options)
        ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])
        ->values()
        ->all();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        x-data="{
            search: '',
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            options: @js($optionsForJs),
            get filteredOptions() {
                const q = this.search.toLowerCase().trim();
                if (!q) return this.options;
                return this.options.filter(o => o.label.toLowerCase().includes(q));
            },
            get filteredValues() {
                return this.filteredOptions.map(o => o.value);
            },
            get selectedOptions() {
                const state = Array.isArray(this.state) ? this.state.map(String) : [];
                return this.options.filter(o => state.includes(o.value));
            },
            selectAll() {
                const state = Array.isArray(this.state) ? [...this.state.map(String)] : [];
                this.filteredValues.forEach(v => {
                    if (!state.includes(v)) state.push(v);
                });
                this.state = state;
            },
            removeOption(value) {
                const state = Array.isArray(this.state) ? this.state.map(String) : [];
                this.state = state.filter(v => v !== String(value));
            },
        }"
        class="fi-fo-checkbox-list space-y-3"
    >
        @unless ($isDisabled)
            {{-- Search + Select All --}}
            <div class="flex gap-2 items-center">
                <x-filament::input.wrapper
                    inline-prefix
                    :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
                    class="flex-1"
                >
                    <input
                        type="search"
                        placeholder="{{ __('Search styles\u2026') }}"
                        x-model.debounce.300ms="search"
                        class="fi-input fi-input-has-inline-prefix"
                    />
                </x-filament::input.wrapper>

                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    size="sm"
                    x-on:click="selectAll()"
                >
                    Select all
                </x-filament::button>
            </div>
        @endunless

        {{-- Scrollable checkbox list --}}
        <div class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 dark:border-white/10 divide-y divide-gray-100 dark:divide-white/5">
            @forelse ($options as $value => $label)
                <div
                    wire:key="{{ $livewireKey }}.options.{{ $value }}"
                    x-show="!search.trim() || filteredValues.includes('{{ e($value) }}')"
                    class="fi-fo-checkbox-list-option-ctn px-3 py-2"
                >
                    <label class="fi-fo-checkbox-list-option flex items-center gap-2.5 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            {{
                                $extraInputAttributeBag
                                    ->merge([
                                        'disabled' => $isDisabled || $isOptionDisabled($value, $label),
                                        'value' => e($value),
                                        'wire:loading.attr' => 'disabled',
                                        $wireModelAttribute => $statePath,
                                    ], escape: false)
                                    ->class([
                                        'fi-checkbox-input',
                                        'fi-valid' => ! $errors->has($statePath),
                                        'fi-invalid' => $errors->has($statePath),
                                    ])
                            }}
                        />
                        <span class="fi-fo-checkbox-list-option-label text-sm text-gray-700 dark:text-gray-200">
                            @if ($isHtmlAllowed)
                                {!! $label !!}
                            @else
                                {{ $label }}
                            @endif
                        </span>
                    </label>
                </div>
            @empty
                <div class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center" wire:key="{{ $livewireKey }}.empty">
                    No styles available.
                </div>
            @endforelse
        </div>

        {{-- No search results --}}
        <p
            x-cloak
            x-show="search.trim() && filteredOptions.length === 0"
            class="text-sm text-center text-gray-500 dark:text-gray-400"
        >
            No styles match your search.
        </p>

        {{-- Selected pills --}}
        <div class="space-y-1.5">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Selected</p>

            <p
                x-show="selectedOptions.length === 0"
                class="text-sm text-gray-500 dark:text-gray-400"
            >
                No styles selected.
            </p>

            <div
                x-cloak
                x-show="selectedOptions.length > 0"
                class="flex flex-wrap gap-1.5"
            >
                <template x-for="option in selectedOptions" :key="option.value">
                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300 px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-primary-500/20">
                        <span x-text="option.label"></span>
                        @unless ($isDisabled)
                            <button
                                type="button"
                                x-on:click="removeOption(option.value)"
                                class="ml-0.5 -mr-0.5 flex-shrink-0 rounded-full p-0.5 text-primary-500 hover:bg-primary-100 dark:hover:bg-primary-900 hover:text-primary-700"
                                aria-label="Remove"
                            >
                                <svg class="h-2.5 w-2.5" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                    <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                                </svg>
                            </button>
                        @endunless
                    </span>
                </template>
            </div>
        </div>
    </div>
</x-dynamic-component>
