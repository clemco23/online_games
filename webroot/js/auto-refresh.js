(() => {
    const boot = () => {
        document.querySelectorAll('[data-auto-refresh]').forEach((root) => {
            const interval = Number.parseInt(root.dataset.refreshInterval || '3000', 10);
            const timer = window.setInterval(() => {
                window.location.reload();
            }, Number.isNaN(interval) ? 3000 : interval);

            root.addEventListener('submit', () => {
                window.clearInterval(timer);
            }, true);

            root.addEventListener('click', (event) => {
                if (event.target instanceof Element && event.target.closest('a')) {
                    window.clearInterval(timer);
                }
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
