<x-page :title="$appTitle . ' — ' . __('auth.register')">
<x-auth-card>
    <h1 class="text-2xl font-bold text-amber-900 dark:text-amber-300 mb-2">{{ $appTitle }}</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('auth.register_intro') }}</p>
    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 px-4 py-3 text-sm text-amber-800 dark:text-amber-300 mb-6">
        After registering, an admin will need to approve your account before you can log in. You'll get an email once it's approved.
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
</x-auth-card>
</x-page>
