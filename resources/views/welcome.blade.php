@php
    /** @var \Illuminate\Support\Collection<string, \Illuminate\Database\Eloquent\Collection<int, \App\Models\Competition>> $competitions */
@endphp
<x-page :title="$appTitle">
<body class="min-h-screen bg-amber-50 dark:bg-gray-950 text-gray-900 dark:text-white">

    <x-app-nav />

    <main class="max-w-3xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-semibold text-amber-900 dark:text-amber-300 mb-6">Competitions</h1>

        @php
            $sections = [
                'judging' => 'Judging',
                'open'    => 'Open',
                'closed'  => 'Past',
            ];
        @endphp

        @if($competitions->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-gray-400 dark:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-4.5A3.75 3.75 0 0012 10.5a3.75 3.75 0 00-3.75 3.75v4.5m3.75-9V3.75m0 0h3m-3 0h-3" />
                </svg>
                <p class="text-sm">No competitions yet.</p>
            </div>
        @else
            @foreach($sections as $status => $label)
                @php
                    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Competition> $statusGroup */
                    $statusGroup = $competitions->get($status) ?? new \Illuminate\Database\Eloquent\Collection();
                @endphp
                @if($statusGroup->isNotEmpty())
                    <section class="mb-8">
                        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">{{ $label }}</h2>
                        <div class="flex flex-col gap-3">
                            @foreach($statusGroup as $competition)
                                <div class="bg-white dark:bg-gray-900 border rounded-lg shadow-sm px-5 py-4 hover:shadow-md transition-shadow
                                    {{ $status === 'judging' ? 'border-amber-400/60 dark:border-amber-600/40' : ($status === 'open' ? 'border-amber-200/70 dark:border-amber-800/50' : 'border-gray-200/80 dark:border-gray-700/80') }}
                                ">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $competition->name }}</p>
                                            @if($competition->judging_date)
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                                    {{ \Carbon\Carbon::parse($competition->judging_date)->format('j M Y') }}
                                                </p>
                                            @endif
                                        </div>
                                        <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full
                                            @if($status === 'judging') bg-amber-600 text-white
                                            @elseif($status === 'open') bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300
                                            @else bg-stone-100 dark:bg-gray-800 text-stone-600 dark:text-gray-400
                                            @endif
                                        ">
                                            {{ $label }}
                                        </span>
                                    </div>

                                    @if($status === 'judging')
                                        <div class="mt-3 pt-3 border-t border-amber-100 dark:border-amber-900">
                                            <a href="{{ route('judging.show', $competition) }}"
                                               class="text-sm text-amber-700 dark:text-amber-400 font-medium hover:underline">
                                                Judge entries &rarr;
                                            </a>
                                        </div>
                                    @elseif($status === 'open')
                                        <div class="mt-3 pt-3 border-t border-amber-100 dark:border-amber-800/50">
                                            <a href="{{ route('competitions.entries.create', $competition) }}"
                                               class="text-sm text-amber-700 dark:text-amber-400 font-medium hover:underline">
                                                Register entry &rarr;
                                            </a>
                                        </div>
                                    @elseif($competition->isClosed())
                                        <div class="mt-3 pt-3 border-t border-stone-100 dark:border-gray-800">
                                            <a href="{{ route('results.show', [$competition, auth()->user()]) }}"
                                               class="text-sm text-amber-700 dark:text-amber-400 font-medium hover:underline">
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
</x-page>
