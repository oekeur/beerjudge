@php
    /** @var \App\Models\Member $member */
    $clubName = is_string($v = config('app.club_name')) ? $v : '';
    $appTitle = $clubName !== '' ? $clubName . ' BeerJudge' : 'BeerJudge';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('profile.title') }} · {{ $appTitle }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-amber-50 text-gray-900">

    <nav class="bg-white border-b border-amber-200 px-6 py-4">
        <div class="max-w-3xl mx-auto flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-lg font-semibold text-amber-900 tracking-tight hover:text-amber-700">
                {{ $appTitle }}
            </a>
            <div class="flex items-center gap-4">
                @if($member->is_admin)
                    <a href="/admin" class="text-sm text-amber-700 hover:text-amber-900 hover:underline">
                        {{ __('nav.admin') }}
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                        {{ __('nav.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-xl mx-auto px-6 py-10 space-y-8">
        <h1 class="text-2xl font-semibold text-amber-900">{{ __('profile.title') }}</h1>

        @if(is_string($status = session('status')) && $status !== '')
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                {{ $status }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <section class="bg-white border border-gray-200 rounded-xl shadow-sm px-6 py-5 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-400">
                    {{ __('profile.account_details') }}
                </h2>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('profile.name') }}
                    </label>
                    <input
                        id="name" name="name" type="text"
                        value="{{ is_string($v = old('name', $member->name)) ? $v : $member->name }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('name') border-red-400 @enderror"
                        required
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('profile.email') }}
                    </label>
                    <input
                        id="email" name="email" type="email"
                        value="{{ is_string($v = old('email', $member->email)) ? $v : $member->email }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('email') border-red-400 @enderror"
                        required
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="locale" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('profile.language') }}
                    </label>
                    @php
                        $currentLocale = is_string($v = old('locale', $member->locale)) ? $v : 'en';
                    @endphp
                    <select
                        id="locale" name="locale"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('locale') border-red-400 @enderror"
                    >
                        <option value="en" @selected($currentLocale === 'en')>English</option>
                        <option value="nl" @selected($currentLocale === 'nl')>Nederlands</option>
                    </select>
                    @error('locale')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="bg-white border border-gray-200 rounded-xl shadow-sm px-6 py-5 space-y-4">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-widest text-gray-400">
                        {{ __('profile.change_password') }}
                    </h2>
                    <p class="text-xs text-gray-400 mt-1">{{ __('profile.password_hint') }}</p>
                </div>

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('profile.current_password') }}
                    </label>
                    <input
                        id="current_password" name="current_password" type="password"
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('current_password') border-red-400 @enderror"
                    >
                    @error('current_password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('profile.new_password') }}
                    </label>
                    <input
                        id="password" name="password" type="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 @error('password') border-red-400 @enderror"
                    >
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('profile.confirm_password') }}
                    </label>
                    <input
                        id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
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
</html>
