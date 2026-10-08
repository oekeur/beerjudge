<x-page :title="$appTitle . ' — ' . __('auth.login')">
<x-auth-card>
    <h1 class="text-2xl font-bold text-amber-900 dark:text-amber-300 mb-6">{{ $appTitle }}</h1>
    @if (session('success'))
        <p class="text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 rounded-lg px-3 py-2 text-sm mb-4" data-testid="flash-success">
            {{ is_string(session('success')) ? session('success') : '' }}
        </p>
    @endif
    <form method="POST" action="/login">
        @csrf
        @if ($errors->any())
            <p class="text-red-600 dark:text-red-400 text-sm mb-4" data-testid="form-error">{{ $errors->first() }}</p>
        @endif
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('auth.email') }}</label>
            <input type="email" id="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                required autofocus>
        </div>
        <div class="mb-6">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('auth.password') }}</label>
            <input type="password" id="password" name="password"
                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                required>
        </div>
        <p class="text-right text-xs mb-6 -mt-4">
            <a href="{{ route('password.request') }}" class="text-amber-700 dark:text-amber-400 hover:underline">
                {{ __('auth.forgot_password') }}
            </a>
        </p>
        <button type="submit"
            class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700">
            {{ __('auth.login') }}
        </button>
        <p class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('auth.magic-link.request') }}" class="text-amber-700 dark:text-amber-400 hover:underline">
                {{ __('auth.magic_link_login') }}
            </a>
        </p>
    </form>
</x-auth-card>
</x-page>
