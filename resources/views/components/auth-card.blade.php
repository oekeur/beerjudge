<body class="min-h-screen bg-amber-50 dark:bg-gray-950 flex items-center justify-center relative">
    <div class="absolute top-4 right-4">
        <x-theme-toggle />
    </div>
    <div class="w-full max-w-sm bg-white dark:bg-gray-900 rounded-xl shadow dark:shadow-black/30 p-8">
        {{ $slot }}
    </div>
</body>
