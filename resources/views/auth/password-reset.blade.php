<x-page :title="$appTitle . ' — ' . __('auth.password_reset_title')">
<x-auth-card>
    <h1 class="text-2xl font-bold text-amber-900 dark:text-amber-300 mb-6">{{ __('auth.password_reset_title') }}</h1>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        @if ($errors->any())
            <p class="text-red-600 dark:text-red-400 text-sm mb-4">{{ $errors->first() }}</p>
        @endif
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('auth.password_reset_new_password') }}</label>
            <input type="password" id="password" name="password"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                required autofocus>
        </div>
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('auth.password_reset_confirm') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                required>
        </div>
        <button type="submit"
            class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700">
            {{ __('auth.password_reset_submit') }}
        </button>
        <p class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('login') }}" class="text-amber-700 dark:text-amber-400 hover:underline">
                {{ __('auth.back_to_login') }}
            </a>
        </p>
    </form>
</x-auth-card>
</x-page>
