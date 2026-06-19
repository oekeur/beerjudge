<script>
    (function () {
        var t = 'system';
        try { t = localStorage.getItem('theme') || 'system'; } catch (e) {}
        var isDark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', isDark);
    })();
</script>
