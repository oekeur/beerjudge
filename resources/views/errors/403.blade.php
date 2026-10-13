@php
    $clubName = is_string($v = config('app.club_name')) ? $v : '';
    $appTitle = $clubName !== '' ? $clubName . ' BeerJudge' : 'BeerJudge';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#b45309">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title>{{ $appTitle }} — {{ __('errors.403.title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-amber-50 flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white rounded-xl shadow p-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-2">{{ $appTitle }}</h1>
        <p class="text-gray-500 text-sm mb-6">{{ __('errors.403.title') }}</p>
        <p class="text-gray-700 text-sm mb-6">{{ __('errors.403.body') }}</p>
        <a href="/" class="block text-center text-sm text-amber-700 hover:underline">{{ __('errors.back_home') }}</a>
    </div>
</body>
</html>
