<script>
(() => {
    const storageKey = 'smartsoft-client-theme';
    function applyTheme(theme) {
        const isLight = theme === 'light';
        document.body.classList.toggle('light-mode', isLight);
        document.documentElement.dataset.clientTheme = isLight ? 'light' : 'dark';
        document.querySelectorAll('[data-theme-label]').forEach(label => {
            label.textContent = isLight ? 'Dark' : 'Light';
        });
    }
    let savedTheme = 'dark';
    try {
        savedTheme = localStorage.getItem(storageKey) || 'dark';
    } catch (_) {}
    applyTheme(savedTheme);
    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(document.body.classList.contains('light-mode') ? 'light' : 'dark');
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('[data-switch-theme]')) return;
        event.preventDefault();
        const theme = document.body.classList.contains('light-mode') ? 'dark' : 'light';
        applyTheme(theme);
        try {
            localStorage.setItem(storageKey, theme);
        } catch (_) {}
    });
})();
</script>
