<x-page :title="'Results — ' . $competition->name">
<body class="bg-gray-50 dark:bg-gray-950 font-sans p-6 max-w-2xl mx-auto">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $competition->name }} — Results</h1>
            @if($isAdmin)
            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">{{ $members->count() }} participants</p>
            @endif
        </div>
        <x-theme-toggle />
    </div>

    @if($ownEntry || $isJudge)
    <div class="space-y-2 mb-10">
        @if($ownEntry)
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center">
            <div class="font-medium text-gray-900 dark:text-white">Your entry</div>
            <a href="{{ route('results.show', [$competition, $ownEntry->member]) }}"
               class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                View sheet
            </a>
        </div>
        @endif
        @if($isJudge)
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center">
            <div class="font-medium text-gray-900 dark:text-white">Your judge report</div>
            <a href="{{ route('judge-report.show', [$competition, $viewerId]) }}"
               class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                View judge report
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
        <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-300 dark:border-gray-700 pb-1 mb-3">Full results</h2>
        <div class="space-y-2">
            @foreach($members as $member)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center">
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $member->email }}</div>
                </div>
                <a href="{{ route('results.show', [$competition, $member]) }}"
                   class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                    View sheet
                </a>
            </div>
            @endforeach
        </div>

        @if($judges->isNotEmpty())
        <div class="mt-10">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-300 dark:border-gray-700 pb-1 mb-3">Judge Reports</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">For judges who scored this competition — your own scores compared with the other judges'.</p>
            <div class="space-y-2">
                @foreach($judges as $judge)
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex justify-between items-center">
                    <div class="font-medium text-gray-900 dark:text-white">{{ $judge->name }}</div>
                    <a href="{{ route('judge-report.show', [$competition, $judge]) }}"
                       class="bg-amber-600 text-white px-3 py-1.5 rounded text-sm hover:bg-amber-700">
                        View judge report
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
