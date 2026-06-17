@php
    $clubName = is_string($v = config('app.club_name')) ? $v : '';
    $appTitle = $clubName !== '' ? $clubName . ' BeerJudge' : 'BeerJudge';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appTitle }} — Log in</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-amber-50 flex items-center justify-center">
    <div class="w-full max-w-sm bg-white rounded-xl shadow p-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-6">{{ $appTitle }}</h1>
        <form method="POST" action="/login">
            @csrf
            @if ($errors->any())
                <p class="text-red-600 text-sm mb-4">{{ $errors->first() }}</p>
            @endif
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.email') }}</label>
                <input type="email" id="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    required autofocus>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.password') }}</label>
                <input type="password" id="password" name="password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    required>
            </div>
            <p class="text-right text-xs mb-6 -mt-4">
                <a href="{{ route('password.request') }}" class="text-amber-700 hover:underline">
                    {{ __('auth.forgot_password') }}
                </a>
            </p>
            <button type="submit"
                class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700">
                {{ __('auth.login') }}
            </button>
            <p class="mt-4 text-center text-sm text-gray-500">
                <a href="{{ route('auth.magic-link.request') }}" class="text-amber-700 hover:underline">
                    {{ __('auth.magic_link_login') }}
                </a>
            </p>
        </form>
    </div>
</body>
</html>
