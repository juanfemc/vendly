document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('landing-mobile-menu');

    if (!menuToggle) {
        return;
    }

    document.querySelectorAll('[data-menu-close]').forEach((item) => {
        item.addEventListener('click', () => {
            menuToggle.checked = false;
        });
    });
});
