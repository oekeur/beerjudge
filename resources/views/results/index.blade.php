<x-page :title="__('results.page_title', ['competition' => $competition->name])">
<body class="bg-gray-50 dark:bg-gray-950 font-sans p-6 max-w-2xl mx-auto">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('results.heading', ['competition' => $competition->name]) }}</h1>
            @if($isAdmin)
            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">{{ trans_choice('results.participants', $members->count()) }}</p>
            @endif
        </div>
        <x-theme-toggle />
    </div>

    @if($ownEntry || $isJudge)
    <div class="space-y-2 mb-10">
        @if($ownEntry)
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center">
            <div class="font-medium text-gray-900 dark:text-white">{{ __('results.your_entry') }}</div>
            <a href="{{ route('results.show', [$competition, $ownEntry->member]) }}"
               class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                {{ __('results.view_sheet') }}
            </a>
        </div>
        @endif
        @if($isJudge)
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center">
            <div class="font-medium text-gray-900 dark:text-white">{{ __('results.your_judge_report') }}</div>
            <a href="{{ route('judge-report.show', [$competition, $viewerId]) }}"
               class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                {{ __('results.view_judge_report') }}
            </a>
        </div>
        @endif
    </div>
    @endif

    <div class="mb-10">
        @include('results.partials.full-standings', ['currentMemberId' => $viewerId])
    </div>

    @if($isAdmin)
    <div>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-300 dark:border-gray-700 pb-1 mb-3">{{ __('results.full_results') }}</h2>
        <div class="space-y-2">
            @foreach($members as $member)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center">
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $member->email }}</div>
                </div>
                <a href="{{ route('results.show', [$competition, $member]) }}"
                   class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                    {{ __('results.view_sheet') }}
                </a>
            </div>
            @endforeach
        </div>

        @if($judges->isNotEmpty())
        <div class="mt-10">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-300 dark:border-gray-700 pb-1 mb-3">{{ __('results.judge_reports') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ __('results.judge_reports_intro') }}</p>
            <div class="space-y-2">
                @foreach($judges as $judge)
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center">
                    <div class="font-medium text-gray-900 dark:text-white">{{ $judge->name }}</div>
                    <a href="{{ route('judge-report.show', [$competition, $judge]) }}"
                       class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                        {{ __('results.view_judge_report') }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif
</body>
</x-page>
