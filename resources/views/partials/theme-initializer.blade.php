<script>
    (() => {
        const storageKey = 'risfm-theme';

        try {
            const storedTheme = localStorage.getItem(storageKey);
            const theme = storedTheme === 'dark' || storedTheme === 'light'
                ? storedTheme
                : 'light';

            document.documentElement.setAttribute('data-bs-theme', theme);
            document.documentElement.style.colorScheme = theme;
        } catch (error) {
            document.documentElement.setAttribute('data-bs-theme', 'light');
        }
    })();
</script>
