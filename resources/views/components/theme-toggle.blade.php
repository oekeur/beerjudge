<div id="theme-toggle" class="flex items-center rounded-lg bg-black/10 dark:bg-white/10 p-0.5 gap-0.5">
    <button type="button" data-theme="light" title="{{ __('nav.theme.light') }}"
        class="p-1 sm:p-1.5 rounded-md text-current opacity-50 hover:opacity-100 transition-opacity">
        {{-- Sun --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" aria-hidden="true">
            <path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM15.657 5.404a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM6.464 14.596a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10ZM14.596 15.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06L5.404 4.343a.75.75 0 0 0-1.06 1.06l1.06 1.06Z"/>
        </svg>
        <span class="sr-only">{{ __('nav.theme.light') }}</span>
    </button>
    <button data-theme="system" title="{{ __('nav.theme.system') }}"
        class="p-1 sm:p-1.5 rounded-md text-current opacity-50 hover:opacity-100 transition-opacity">
        {{-- Computer Desktop --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" aria-hidden="true">
            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.25A2.25 2.25 0 0 1 4.25 2h11.5A2.25 2.25 0 0 1 18 4.25v8.5A2.25 2.25 0 0 1 15.75 15h-3.105a3.501 3.501 0 0 0 1.1 1.677A.75.75 0 0 1 13.26 18H6.74a.75.75 0 0 1-.484-1.323A3.501 3.501 0 0 0 7.355 15H4.25A2.25 2.25 0 0 1 2 12.75v-8.5Zm1.5 0a.75.75 0 0 1 .75-.75h11.5a.75.75 0 0 1 .75.75v7.5a.75.75 0 0 1-.75.75H4.25a.75.75 0 0 1-.75-.75v-7.5Z"/>
        </svg>
        <span class="sr-only">{{ __('nav.theme.system') }}</span>
    </button>
    <button type="button" data-theme="dark" title="{{ __('nav.theme.dark') }}"
        class="p-1 sm:p-1.5 rounded-md text-current opacity-50 hover:opacity-100 transition-opacity">
        {{-- Moon --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd"/>
        </svg>
        <span class="sr-only">{{ __('nav.theme.dark') }}</span>
    </button>
</div>
<script>
    (function () {
        var toggle = document.getElementById('theme-toggle');
        if (!toggle) return;

        function applyTheme(t) {
            if (t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            toggle.querySelectorAll('button').forEach(function (btn) {
                var active = btn.dataset.theme === t;
                btn.style.opacity = active ? '1' : '';
                btn.style.background = active ? 'rgba(255,255,255,0.2)' : '';
            });
        }

        var media = window.matchMedia('(prefers-color-scheme: dark)');

        function setTheme(t) {
            localStorage.setItem('theme', t);
            applyTheme(t);

            media.removeEventListener('change', onSystemChange);

            if (t === 'system') {
                media.addEventListener('change', onSystemChange);
            }
        }

        function onSystemChange(e) {
            if ((localStorage.getItem('theme') || 'system') === 'system') {
                document.documentElement.classList.toggle('dark', e.matches);
            }
        }

        var current = localStorage.getItem('theme') || 'system';
        applyTheme(current);

        if (current === 'system') {
            media.addEventListener('change', onSystemChange);
        }

        toggle.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () { setTheme(btn.dataset.theme); });
        });
    })();
</script>
