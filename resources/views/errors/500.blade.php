@php
    $sentryDsn = is_string($v = config('sentry.dsn')) ? $v : '';
    $eventId = null;
    if ($sentryDsn !== '') {
        try {
            $eventId = \Sentry\SentrySdk::getCurrentHub()->getLastEventId();
        } catch (\Throwable) {
            // Sentry unavailable; skip report dialog
        }
    }

    $userName = null;
    $userEmail = null;
    try {
        $authUser = auth()->user();
        if ($authUser !== null) {
            $userName = is_string($authUser->name) ? $authUser->name : null;
            $userEmail = is_string($authUser->email) ? $authUser->email : null;
        }
    } catch (\Throwable) {
        // Corrupted session; proceed without pre-fill
    }

    $clubName = is_string($v = config('app.club_name')) ? $v : '';
    $appTitle = $clubName !== '' ? $clubName . ' BeerJudge' : 'BeerJudge';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appTitle }} — Server error</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @if ($sentryDsn !== '' && $eventId !== null)
        <script src="https://browser.sentry-cdn.com/10.58.0/bundle.min.js" crossorigin="anonymous"></script>
    @endif
</head>
<body class="min-h-screen bg-amber-50 flex items-center justify-center">
    <div class="w-full max-w-sm bg-white rounded-xl shadow p-8">
        <h1 class="text-2xl font-bold text-amber-900 mb-2">{{ $appTitle }}</h1>
        <p class="text-gray-500 text-sm mb-6">Something went wrong on our end.</p>
        <p class="text-gray-700 text-sm mb-6">
            The error has been logged. If the problem persists, you can use the button below to send a report.
        </p>
        @if ($sentryDsn !== '' && $eventId !== null)
            <button
                id="sentry-report-btn"
                type="button"
                class="w-full bg-amber-600 text-white rounded-lg py-2 text-sm font-medium hover:bg-amber-700 mb-4"
            >
                Send error report
            </button>
        @endif
        <a href="/" class="block text-center text-sm text-amber-700 hover:underline">Back to home</a>
    </div>

    @if ($sentryDsn !== '' && $eventId !== null)
        <script>
            Sentry.init({ dsn: @json($sentryDsn) });

            document.getElementById('sentry-report-btn').addEventListener('click', function () {
                Sentry.showReportDialog({
                    eventId: @json((string) $eventId),
                    @if ($userName !== null || $userEmail !== null)
                    user: {
                        @if ($userName !== null)
                        name: @json($userName),
                        @endif
                        @if ($userEmail !== null)
                        email: @json($userEmail),
                        @endif
                    },
                    @endif
                });
            });
        </script>
    @endif
</body>
</html>
