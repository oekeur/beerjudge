@php
    /** @var \App\Models\Member $member */
@endphp
<x-page :title="__('profile.title') . ' · ' . $appTitle">
<body class="min-h-screen bg-amber-50 dark:bg-gray-950 text-gray-900 dark:text-white">

    <x-app-nav :showProfile="false" />

    <main class="max-w-xl mx-auto px-6 py-10 space-y-8">
        <h1 class="text-2xl font-semibold text-amber-900 dark:text-amber-300">{{ __('profile.title') }}</h1>

        @if(is_string($status = session('status')) && $status !== '')
            <div class="rounded-lg bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-800 dark:text-green-300">
                {{ $status }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <section class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm px-6 py-5 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-400">
                    {{ __('profile.account_details') }}
                </h2>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('profile.name') }}
                    </label>
                    <input
                        id="name" name="name" type="text"
                        value="{{ is_string($v = old('name', $member->name)) ? $v : $member->name }}"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('name') border-red-400 @enderror"
                        required
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('profile.email') }}
                    </label>
                    <input
                        id="email" name="email" type="email"
                        value="{{ is_string($v = old('email', $member->email)) ? $v : $member->email }}"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('email') border-red-400 @enderror"
                        required
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="locale" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('profile.language') }}
                    </label>
                    @php
                        $currentLocale = is_string($v = old('locale', $member->locale)) ? $v : 'en';
                    @endphp
                    <select
                        id="locale" name="locale"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('locale') border-red-400 @enderror"
                    >
                        <option value="en" @selected($currentLocale === 'en')>English</option>
                        <option value="nl" @selected($currentLocale === 'nl')>Nederlands</option>
                    </select>
                    @error('locale')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm px-6 py-5 space-y-4">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-400">
                        {{ __('profile.change_password') }}
                    </h2>
                    <p class="text-xs text-gray-400 mt-1">{{ __('profile.password_hint') }}</p>
                </div>

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('profile.current_password') }}
                    </label>
                    <input
                        id="current_password" name="current_password" type="password"
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('current_password') border-red-400 @enderror"
                    >
                    @error('current_password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('profile.new_password') }}
                    </label>
                    <input
                        id="password" name="password" type="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('password') border-red-400 @enderror"
                    >
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('profile.confirm_password') }}
                    </label>
                    <input
                        id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    >
                </div>
            </section>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="rounded-lg bg-amber-700 text-white px-5 py-2 text-sm font-medium hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
                    {{ __('profile.save') }}
                </button>
            </div>
        </form>
    </main>

</body>
</x-page>
