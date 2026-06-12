@php
    $clubName = is_string($v = config('app.club_name')) ? $v : '';
    $appTitle = $clubName !== '' ? $clubName . ' BeerJudge' : 'BeerJudge';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appTitle }} — {{ __('auth.magic_link_title') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-amber-50 flex items-center justify-center">
    <div class="w-full max-w-sm bg-white rounded-xl shadow p-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-2">{{ $appTitle }}</h1>
        <p class="text-sm text-gray-500 mb-6">{{ __('auth.magic_link_description') }}</p>
        @if (session('success'))
            <p class="text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-sm mb-4">
                {{ is_string(session('success')) ? session('success') : '' }}
            </p>
        @endif
        <form method="POST" action="{{ route('auth.magic-link.request') }}">
            @csrf
            @if ($errors->any())
                <p class="text-red-600 text-sm mb-4">{{ $errors->first() }}</p>
            @endif
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.email') }}</label>
                <input type="email" id="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    required autofocus>
            </div>
            <button type="submit"
                class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700">
                {{ __('auth.magic_link_send') }}
            </button>
            <p class="mt-4 text-center text-sm text-gray-500">
                <a href="{{ route('login') }}" class="text-amber-700 hover:underline">{{ __('auth.back_to_login') }}</a>
            </p>
        </form>
    </div>
</body>
</html>
