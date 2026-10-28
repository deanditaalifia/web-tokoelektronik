// ElectroMart - Zero-Flicker Dark / Light Theme Manager

(function () {
    const STORAGE_KEY = 'electromart_theme';

    function getSavedTheme() {
        return localStorage.getItem(STORAGE_KEY) || 'dark';
    }

    function updateIcons(theme) {
        document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                if (theme === 'dark') {
                    icon.className = 'bi bi-sun-fill text-warning';
                    btn.setAttribute('title', 'Ganti ke Mode Terang');
                } else {
                    icon.className = 'bi bi-moon-stars-fill text-primary';
                    btn.setAttribute('title', 'Ganti ke Mode Gelap');
                }
            }
        });
    }

    function switchTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        updateIcons(theme);
    }

    // Attach click events on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        const currentTheme = getSavedTheme();
        document.documentElement.setAttribute('data-bs-theme', currentTheme);
        updateIcons(currentTheme);

        document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const now = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                switchTheme(now);
            });
        });
    });
})();
