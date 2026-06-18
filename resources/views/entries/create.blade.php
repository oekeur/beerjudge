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
    <meta name="sentry-dsn" content="{{ config('sentry.dsn') ?? '' }}">
    <meta name="sentry-user" content='@json(["name" => auth()->user()?->name, "email" => auth()->user()?->email])'>
</head>
<body class="min-h-screen bg-amber-50">
    <div class="max-w-lg mx-auto py-12 px-4">
        <h1 class="text-2xl font-bold text-amber-900 mb-1">{{ $competition->name }}</h1>
        <p class="text-sm text-gray-500 mb-8">{{ __('entry.subtitle') }}</p>

        @if (session('success'))
            <p class="text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-sm mb-6">
                {{ is_string(session('success')) ? session('success') : '' }}
            </p>
        @endif

        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <h2 class="text-base font-semibold text-gray-800 mb-4">{{ __('entry.add_entry') }}</h2>
            <form method="POST" action="{{ route('competitions.entries.store', $competition) }}">
                @csrf
                @if ($errors->any())
                    <p class="text-red-600 text-sm mb-4">{{ $errors->first() }}</p>
                @endif
                <div class="mb-4">
                    <label for="style_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('entry.style') }}</label>
                    <select id="style_id" name="style_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
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
                    <label for="brewer_notes" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('entry.brewer_notes') }}
                        <span class="text-gray-400 font-normal">({{ __('entry.optional') }})</span>
                    </label>
                    <textarea id="brewer_notes" name="brewer_notes" rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">{{ is_string(old('brewer_notes')) ? old('brewer_notes') : '' }}</textarea>
                </div>
                <button type="submit"
                    class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700">
                    {{ __('entry.submit') }}
                </button>
            </form>
        </div>

        @if ($myEntries->isNotEmpty())
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-3">{{ __('entry.your_entries') }}</h2>
                <ul class="space-y-2">
                    @foreach ($myEntries as $entry)
                        <li class="text-sm text-gray-700">
                            {{ $entry->style?->code }} — {{ $entry->style?->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</body>
</html>
