<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#b45309">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title>Results — {{ $competition->name }}</title>
    @vite(['resources/css/app.css'])
    <x-theme-init />
</head>
<body class="bg-gray-50 dark:bg-gray-950 font-sans p-6 max-w-2xl mx-auto">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $competition->name }} — All Results</h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">{{ $members->count() }} participants</p>
        </div>
        <x-theme-toggle />
    </div>

    <div class="space-y-2">
        @foreach($members as $member)
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center">
            <div>
                <div class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $member->email }}</div>
            </div>
            <a href="{{ route('results.show', [$competition, $member]) }}"
               class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                View sheet
            </a>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        <button onclick="window.open('?print=all')" class="text-sm text-amber-700 dark:text-amber-400 underline">Print all</button>
    </div>
</body>
</html>
