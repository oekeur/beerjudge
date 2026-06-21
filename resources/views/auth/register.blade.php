@php
    $clubName = is_string($v = config('app.club_name')) ? $v : '';
    $appTitle = $clubName !== '' ? $clubName . ' BeerJudge' : 'BeerJudge';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <x-app-head :title="$appTitle . ' — ' . __('auth.register')" />
</head>
<body class="min-h-screen bg-amber-50 dark:bg-gray-950 flex items-center justify-center relative">
    <div class="absolute top-4 right-4">
        <x-theme-toggle />
    </div>
    <div class="w-full max-w-sm bg-white dark:bg-gray-900 rounded-xl shadow dark:shadow-black/30 p-8">
        <h1 class="text-2xl font-bold text-amber-900 dark:text-amber-300 mb-2">{{ $appTitle }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('auth.register_intro') }}</p>
        <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 px-4 py-3 text-sm text-amber-800 dark:text-amber-300 mb-3">
            After registering, an admin will need to approve your account before you can log in.
        </div>
        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 px-4 py-3 text-sm text-blue-800 dark:text-blue-300 mb-6">
            <span class="font-medium">Beta version</span> — expect rough edges. Email notifications are not set up yet, so you won't receive a confirmation email.
        </div>
        <form method="POST" action="{{ url('/join/' . $token) }}">
            @csrf
            @if ($errors->any())
                <p class="text-red-600 dark:text-red-400 text-sm mb-4">{{ $errors->first() }}</p>
            @endif
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('auth.name') }}</label>
                <input type="text" id="name" name="name" value="{{ is_string(old('name')) ? old('name') : '' }}"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    required autofocus>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('auth.email') }}</label>
                <input type="email" id="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('auth.password') }}</label>
                <input type="password" id="password" name="password"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    required>
            </div>
            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('auth.password_confirm') }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                    required>
            </div>
            <button type="submit"
                class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700">
                {{ __('auth.register_submit') }}
            </button>
        </form>
    </div>
</body>
</html>
