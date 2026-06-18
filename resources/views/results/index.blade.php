<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results — {{ $competition->name }}</title>
    @vite(['resources/css/app.css'])
    <meta name="sentry-dsn" content="{{ config('sentry.dsn') ?? '' }}">
    <meta name="sentry-user" content='@json(["name" => auth()->user()?->name, "email" => auth()->user()?->email])'>
</head>
<body class="bg-gray-50 font-sans p-6 max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ $competition->name }} — All Results</h1>
        <p class="text-gray-600 text-sm mt-1">{{ $members->count() }} participants</p>
    </div>

    <div class="space-y-2">
        @foreach($members as $member)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex justify-between items-center">
            <div>
                <div class="font-medium text-gray-900">{{ $member->name }}</div>
                <div class="text-sm text-gray-500">{{ $member->email }}</div>
            </div>
            <a href="{{ route('results.show', [$competition, $member]) }}"
               class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                View sheet
            </a>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        <button onclick="window.open('?print=all')" class="text-sm text-amber-700 underline">Print all</button>
    </div>
</body>
</html>
