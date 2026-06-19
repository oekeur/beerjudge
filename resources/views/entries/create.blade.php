@php
    $clubName = is_string($v = config('app.club_name')) ? $v : '';
    $appTitle = $clubName !== '' ? $clubName . ' BeerJudge' : 'BeerJudge';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appTitle }} — {{ __('entry.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="sentry-dsn" content="{{ is_string(config('sentry.dsn')) ? config('sentry.dsn') : '' }}">
    <meta name="sentry-user" content='@json(["name" => auth()->user()?->name, "email" => auth()->user()?->email])'>
    <x-theme-init />
</head>
<body class="min-h-screen bg-amber-50 dark:bg-gray-950">
    <div class="max-w-lg mx-auto py-12 px-4">
        <div class="flex items-start justify-between mb-1">
            <h1 class="text-2xl font-bold text-amber-900 dark:text-amber-300">{{ $competition->name }}</h1>
            <x-theme-toggle />
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ __('entry.subtitle') }}</p>

        @if (session('success'))
            <p class="text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg px-3 py-2 text-sm mb-6">
                {{ is_string(session('success')) ? session('success') : '' }}
            </p>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow dark:shadow-black/20 p-6 mb-8">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('entry.add_entry') }}</h2>
            <form method="POST" action="{{ route('competitions.entries.store', $competition) }}">
                @csrf
                @if ($errors->any())
                    <p class="text-red-600 dark:text-red-400 text-sm mb-4">{{ $errors->first() }}</p>
                @endif
                <div class="mb-4">
                    <label for="style_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('entry.style') }}</label>
                    <select id="style_id" name="style_id"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                        required>
                        <option value="">{{ __('entry.style_placeholder') }}</option>
                        @foreach ($styles as $style)
                            <option value="{{ $style->id }}" {{ (is_string(old('style_id')) ? old('style_id') : '') === (string) $style->id ? 'selected' : '' }}>
                                {{ $style->code }} — {{ $style->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-6">
                    <label for="brewer_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('entry.brewer_notes') }}
                        <span class="text-gray-400 dark:text-gray-500 font-normal">({{ __('entry.optional') }})</span>
                    </label>
                    <textarea id="brewer_notes" name="brewer_notes" rows="3"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">{{ is_string(old('brewer_notes')) ? old('brewer_notes') : '' }}</textarea>
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
</html>
