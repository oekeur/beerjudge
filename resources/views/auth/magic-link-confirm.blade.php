<x-page :title="$appTitle . ' — ' . __('auth.magic_link_action')">
<x-auth-card>
    <h1 class="text-2xl font-bold text-amber-900 dark:text-amber-300 mb-6">{{ $appTitle }}</h1>
    <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">{{ __('auth.magic_link_confirm_description') }}</p>
    <form method="POST" action="{{ route('auth.magic-link.consume', ['token' => $token]) }}">
        @csrf
        <button type="submit" data-testid="magic-link-login-button"
            class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700">
            {{ __('auth.magic_link_action') }}
        </button>
    </form>
</x-auth-card>
</x-page>
