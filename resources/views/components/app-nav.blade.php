<nav class="bg-white dark:bg-gray-900 border-b border-amber-200 dark:border-gray-800 px-6 py-4">
    <div class="max-w-3xl mx-auto flex items-center justify-between">
        <a href="{{ route('home') }}" class="text-lg font-semibold text-amber-900 dark:text-amber-300 tracking-tight hover:text-amber-700 dark:hover:text-amber-200">
            {{ $appTitle }}
        </a>
        <div class="flex items-center gap-4">
            @if(auth()->user()?->is_admin)
                <a href="/admin" class="text-sm text-amber-700 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-200 hover:underline">
                    {{ __('nav.admin') }}
                </a>
            @endif
            @if(! request()->routeIs('profile.edit'))
                <a href="{{ route('profile.edit') }}" class="text-sm text-amber-700 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-200 hover:underline">
                    {{ __('nav.profile') }}
                </a>
            @endif
            <x-theme-toggle />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:underline">
                    {{ __('nav.logout') }}
                </button>
            </form>
        </div>
    </div>
</nav>
