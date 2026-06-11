@php
    $appTitle = config('app.club_name') ? config('app.club_name').' BeerJudge' : 'BeerJudge';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appTitle }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-amber-50 text-gray-900">

    <nav class="bg-white border-b border-amber-200 px-6 py-4">
        <div class="max-w-3xl mx-auto flex items-center justify-between">
            <span class="text-lg font-semibold text-amber-900 tracking-tight">{{ $appTitle }}</span>
            <div class="flex items-center gap-4">
                @if(auth()->user()->is_admin)
                    <a href="/admin" class="text-sm text-amber-700 hover:text-amber-900 hover:underline">
                        Admin panel
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-3xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-semibold text-amber-900 mb-6">Competitions</h1>

        @php
            $sections = [
                'judging' => 'Judging',
                'open'    => 'Open',
                'closed'  => 'Past',
            ];
        @endphp

        @if($competitions->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-4.5A3.75 3.75 0 0012 10.5a3.75 3.75 0 00-3.75 3.75v4.5m3.75-9V3.75m0 0h3m-3 0h-3" />
                </svg>
                <p class="text-sm">No competitions yet.</p>
            </div>
        @else
            @foreach($sections as $status => $label)
                @if($competitions->has($status))
                    <section class="mb-8">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">{{ $label }}</h2>
                        <div class="flex flex-col gap-3">
                            @foreach($competitions[$status] as $competition)
                                <div class="bg-white border rounded-lg shadow-sm px-5 py-4 hover:shadow-md transition-shadow
                                    {{ $status === 'judging' ? 'border-amber-400/60' : ($status === 'open' ? 'border-amber-200/70' : 'border-gray-200/80') }}
                                ">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $competition->name }}</p>
                                            @if($competition->judging_date)
                                                <p class="text-sm text-gray-500 mt-0.5">
                                                    {{ $competition->judging_date->format('j M Y') }}
                                                </p>
                                            @endif
                                        </div>
                                        <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full
                                            @if($status === 'judging') bg-amber-600 text-white
                                            @elseif($status === 'open') bg-amber-100 text-amber-700
                                            @else bg-stone-100 text-stone-600
                                            @endif
                                        ">
                                            {{ $label }}
                                        </span>
                                    </div>

                                    @if($status === 'judging')
                                        <div class="mt-3 pt-3 border-t border-amber-100">
                                            <a href="{{ route('judging.show', $competition) }}"
                                               class="text-sm text-amber-700 font-medium hover:underline">
                                                Judge entries &rarr;
                                            </a>
                                        </div>
                                    @elseif($competition->isClosed())
                                        <div class="mt-3 pt-3 border-t border-stone-100">
                                            <a href="{{ route('results.show', [$competition, auth()->user()]) }}"
                                               class="text-sm text-amber-700 font-medium hover:underline">
                                                View results &rarr;
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        @endif
    </main>

</body>
</html>
