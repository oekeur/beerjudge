<x-page :title="$appTitle . ' — ' . __('auth.invite_invalid_title')">
<x-auth-card>
    <h1 class="text-2xl font-bold text-amber-900 dark:text-amber-300 mb-6">{{ $appTitle }}</h1>

    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30 mb-4">
        <svg class="h-6 w-6 text-amber-700 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
        </svg>
    </div>

    <h2 data-testid="invite-invalid-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
        {{ $alreadyUsed ? __('auth.invite_used_heading') : __('auth.invite_expired_heading') }}
    </h2>

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        {{ $alreadyUsed ? __('auth.invite_used_body') : __('auth.invite_expired_body') }}
    </p>

    @if (! $alreadyUsed && $expiresAt !== null)
        <p class="text-sm text-gray-500 dark:text-gray-500 mb-4">
            {{ __('auth.invite_expired_at', ['date' => $expiresAt->translatedFormat('j F Y, H:i')]) }}
        </p>
    @endif

    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
        {{ __('auth.invite_next_step') }}
    </div>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('auth.invite_have_account') }}
        <a href="{{ route('login') }}" class="text-amber-700 dark:text-amber-400 hover:underline">{{ __('auth.login') }}</a>
    </p>
</x-auth-card>
</x-page>
