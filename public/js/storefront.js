(function () {
    const page = document.querySelector('.storefront-page');

    if (!page) {
        return;
    }

    const resolveBrandContrast = () => {
        const brandColor = getComputedStyle(page).getPropertyValue('--brand-color').trim();

        if (!brandColor) {
            return;
        }

        const probe = document.createElement('span');
        probe.style.color = brandColor;
        probe.style.display = 'none';
        document.body.appendChild(probe);

        const computedColor = getComputedStyle(probe).color;
        document.body.removeChild(probe);

        const match = computedColor.match(/\d+/g);

        if (!match || match.length < 3) {
            return;
        }

        const [red, green, blue] = match.slice(0, 3).map(Number);
        const luminance = (0.299 * red + 0.587 * green + 0.114 * blue) / 255;
        const contrast = luminance < 0.55 ? '#ffffff' : '#111111';

        page.style.setProperty('--brand-contrast', contrast);
    };

    const forms = document.querySelectorAll('.add-to-cart-form');
    let cartBadge = document.querySelector('.cart-badge');
    const cartLink = document.querySelector('.cart-link');
    const feedback = document.getElementById('cartFeedback');
    const navToggle = document.querySelector('.nav-toggle');
    const navbar = document.querySelector('.navbar');
    const navPanelLinks = document.querySelectorAll('.nav-panel a');
    const csrfToken = page.dataset.csrf || '';
    const addingText = page.dataset.addingText || 'Agregando...';
    const addedText = page.dataset.feedbackAdded || 'Producto agregado al carrito';
    const addErrorText = page.dataset.feedbackError || 'No pudimos agregar el producto';
    let feedbackTimer;

    resolveBrandContrast();

    const showFeedback = (message) => {
        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.classList.add('is-visible');

        window.clearTimeout(feedbackTimer);
        feedbackTimer = window.setTimeout(() => {
            feedback.classList.remove('is-visible');
        }, 1800);
    };

    const updateCartBadge = (count) => {
        if (!cartLink) {
            return;
        }

        if (!cartBadge && count > 0) {
            const badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.textContent = count;
            cartLink.appendChild(badge);
            cartBadge = badge;
            return;
        }

        if (cartBadge) {
            cartBadge.textContent = count;
        }
    };

    const closeMenu = () => {
        if (!navbar || !navToggle) {
            return;
        }

        navbar.classList.remove('is-open');
        page.classList.remove('is-menu-open');
        navToggle.setAttribute('aria-expanded', 'false');
    };

    if (navToggle && navbar) {
        navToggle.addEventListener('click', () => {
            const isOpen = navbar.classList.toggle('is-open');
            page.classList.toggle('is-menu-open', isOpen);
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        navPanelLinks.forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 900) {
                    closeMenu();
                }
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) {
                closeMenu();
            }
        });
    }

    forms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const originalText = button ? button.textContent : '';

            if (button) {
                button.disabled = true;
                button.classList.add('is-loading');
                button.textContent = addingText;
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(form),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || addErrorText);
                }

                updateCartBadge(data.cart_count || 0);
                showFeedback(data.message || addedText);
            } catch (error) {
                showFeedback(error.message || addErrorText);
            } finally {
                if (button) {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                    button.textContent = originalText;
                }
            }
        });
    });
})();
