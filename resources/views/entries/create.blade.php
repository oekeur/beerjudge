<x-page :title="$appTitle . ' — ' . __('entry.title')" :vite="['resources/css/app.css', 'resources/js/app.js']">
<body class="min-h-screen bg-amber-50 dark:bg-gray-950 text-gray-900 dark:text-white">
    <x-app-nav />

    <div class="max-w-lg mx-auto py-12 px-4">
        <h1 class="text-2xl font-bold text-amber-900 dark:text-amber-300 mb-1">{{ $competition->name }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ __('entry.subtitle') }}</p>

        @if (session('success'))
            <p class="text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg px-3 py-2 text-sm mb-6" data-testid="flash-success">
                {{ is_string(session('success')) ? session('success') : '' }}
            </p>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow dark:shadow-black/20 p-6 mb-8">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('entry.add_entry') }}</h2>
            <form method="POST" action="{{ route('competitions.entries.store', $competition) }}">
                @csrf
                @if ($errors->any())
                    <p class="text-red-600 dark:text-red-400 text-sm mb-4" data-testid="form-error">{{ $errors->first() }}</p>
                @endif
                @php
                    $stylesForJs = $styles->map(fn ($s) => ['id' => $s->id, 'label' => $s->code . ' — ' . $s->name])->values();
                    $oldStyleId  = is_string(old('style_id')) ? old('style_id') : '';
                @endphp
                <div class="mb-4"
                    x-data="{
                        open: false,
                        query: '',
                        styleId: '{{ $oldStyleId }}',
                        styles: {{ Js::from($stylesForJs) }},
                        get filtered() {
                            if (!this.query) return this.styles;
                            const q = this.query.toLowerCase();
                            return this.styles.filter(s => s.label.toLowerCase().includes(q));
                        },
                        select(style) {
                            this.styleId = style.id;
                            this.query   = style.label;
                            this.open    = false;
                        },
                        init() {
                            if (this.styleId) {
                                const found = this.styles.find(s => String(s.id) === String(this.styleId));
                                if (found) { this.query = found.label; }
                            }
                        }
                    }"
                    @click.outside="open = false"
                >
                    <label for="style_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('entry.style') }}</label>
                    <input type="hidden" name="style_id" :value="styleId" />
                    <div class="relative">
                        <input id="style_search" type="text"
                            x-model="query"
                            @focus="open = true"
                            @input="open = true; styleId = ''"
                            placeholder="{{ __('entry.style_placeholder') }}"
                            autocomplete="off"
                            required
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                        />
                        <div x-show="open && filtered.length > 0"
                            class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="style in filtered" :key="style.id">
                                <button type="button"
                                    @click="select(style)"
                                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-amber-900/30">
                                    <span x-text="style.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="mb-6">
                    <label for="brewer_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('entry.brewer_notes') }}
                        <span class="text-gray-400 dark:text-gray-500 font-normal">({{ __('entry.optional') }})</span>
                    </label>
                    <textarea id="brewer_notes" name="brewer_notes" rows="3"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">{{ is_string(old('brewer_notes')) ? old('brewer_notes') : '' }}</textarea>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ __('entry.brewer_notes_hint') }}</p>
                </div>
                <button type="submit"
                    class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700">
                    {{ __('entry.submit') }}
                </button>
            </form>
        </div>

        @if ($myEntries->isNotEmpty())
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow dark:shadow-black/20 p-6">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-3">{{ __('entry.your_entries') }}</h2>
                <ul class="space-y-2">
                    @foreach ($myEntries as $entry)
                        <li class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $entry->style?->code }} — {{ $entry->style?->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</body>
</x-page>
