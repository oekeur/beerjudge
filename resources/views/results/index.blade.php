<!DOCTYPE html>
<html lang="en">
<head>
    <x-app-head :title="'Results — ' . $competition->name" />
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
