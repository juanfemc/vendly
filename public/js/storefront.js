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
    const cartLink = document.querySelector('.cart-link');
    const feedback = document.getElementById('cartFeedback');
    const cartDrawer = document.querySelector('[data-cart-drawer]');
    const cartDrawerToggle = document.getElementById('minimalShopCartToggle');
    const cartDrawerItems = document.querySelector('[data-cart-drawer-items]');
    const cartDrawerCount = document.querySelector('[data-cart-drawer-count]');
    const cartDrawerSubtotal = document.querySelector('[data-cart-drawer-subtotal]');
    const cartDrawerShipping = document.querySelector('[data-cart-drawer-shipping]');
    const cartDrawerTax = document.querySelector('[data-cart-drawer-tax]');
    const cartDrawerTotal = document.querySelector('[data-cart-drawer-total]');
    const navToggle = document.querySelector('.nav-toggle');
    const navbar = document.querySelector('.navbar');
    const navClose = document.querySelector('.nav-close');
    const navBackdrop = document.querySelector('.nav-backdrop');
    const navPanelLinks = document.querySelectorAll('.nav-panel a');
    const navDropdowns = document.querySelectorAll('.nav-dropdown');
    const announcementMessages = Array.from(document.querySelectorAll('[data-announcement-message]'));
    const storefrontTopbar = document.querySelector('[data-storefront-topbar]');
    const csrfToken = page.dataset.csrf || '';
    const addingText = page.dataset.addingText || 'Agregando...';
    const addedText = page.dataset.feedbackAdded || 'Producto agregado al carrito';
    const addErrorText = page.dataset.feedbackError || 'No pudimos agregar el producto';
    let feedbackTimer;

    resolveBrandContrast();

    const syncTopbarHeight = () => {
        if (!storefrontTopbar) {
            page.style.setProperty('--storefront-topbar-height', '0px');
            return;
        }

        page.style.setProperty('--storefront-topbar-height', `${storefrontTopbar.offsetHeight}px`);
    };

    syncTopbarHeight();
    window.addEventListener('load', syncTopbarHeight);
    window.addEventListener('resize', syncTopbarHeight);

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

    const formatMoney = (value) => `$${Number(value || 0).toFixed(2)}`;
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const updateCartBadge = (count) => {
        if (!cartLink) {
            return;
        }

        const ensureBadge = (link) => {
            let badge = link.querySelector('.cart-badge');

            if (!badge && count > 0) {
                badge = document.createElement('span');
                badge.className = 'cart-badge';
                link.appendChild(badge);
            }

            return badge;
        };

        document.querySelectorAll('.cart-link').forEach((link) => {
            const badge = ensureBadge(link);

            if (!badge) {
                return;
            }

            badge.textContent = count;
            badge.hidden = count < 1;
        });
    };

    const renderCartDrawer = (data) => {
        if (!cartDrawer || !cartDrawerItems) {
            return;
        }

        const items = Array.isArray(data.cart_items) ? data.cart_items : [];
        const subtotal = Number(data.total || 0);
        const shipping = Number(cartDrawer.dataset.cartShipping || 0);
        const tax = Number(cartDrawer.dataset.cartTax || 0);
        const count = Number(data.cart_count || 0);

        if (cartDrawerCount) {
            cartDrawerCount.textContent = count;
        }

        cartDrawer.classList.toggle('is-empty', items.length < 1);

        if (cartDrawerSubtotal) {
            cartDrawerSubtotal.textContent = formatMoney(subtotal);
        }

        if (cartDrawerShipping) {
            cartDrawerShipping.textContent = shipping > 0 ? formatMoney(shipping) : 'Gratis';
        }

        if (cartDrawerTax) {
            cartDrawerTax.textContent = formatMoney(tax);
        }

        if (cartDrawerTotal) {
            cartDrawerTotal.textContent = formatMoney(subtotal + shipping + tax);
        }

        cartDrawer.dataset.cartSubtotal = String(subtotal);

        if (!items.length) {
            const storeUrl = escapeHtml(cartDrawer.dataset.storeUrl || '/');
            cartDrawerItems.innerHTML = `
                <div class="minimal-shop-cart-empty" data-cart-drawer-empty>
                    <strong>Tu carrito está vacío</strong>
                    <a href="${storeUrl}">Volver a la tienda</a>
                </div>
            `;
            return;
        }

        cartDrawerItems.innerHTML = items.map((item) => {
            const name = escapeHtml(item.name || 'Producto');
            const imageUrl = escapeHtml(item.image_url || '');
            const key = escapeHtml(item.key || '');
            const image = item.image_url
                ? `<img src="${imageUrl}" alt="${name}">`
                : `<span>${escapeHtml(String(item.name || 'P').charAt(0).toUpperCase())}</span>`;
            const detail = escapeHtml(item.color || item.size || 'Sin variante');
            const badge = escapeHtml(item.color || 'Otros');

            return `
                <article class="minimal-shop-cart-item" data-cart-drawer-item data-cart-key="${key}">
                    <div class="minimal-shop-cart-thumb">${image}</div>
                    <div class="minimal-shop-cart-info">
                        <span>${badge}</span>
                        <strong>${name}</strong>
                        <small>${detail}</small>
                        <b data-cart-item-total>${formatMoney(item.item_total || 0)}</b>
                    </div>
                    <div class="minimal-shop-cart-controls">
                        <button type="button" data-cart-drawer-minus aria-label="Restar">−</button>
                        <span data-cart-drawer-quantity>${item.quantity || 1}</span>
                        <button type="button" data-cart-drawer-plus aria-label="Sumar">+</button>
                        <button type="button" data-cart-drawer-remove aria-label="Eliminar">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 14h10l1-14"></path><path d="M9 7V4h6v3"></path></svg>
                        </button>
                    </div>
                </article>
            `;
        }).join('');
    };

    const sendCartDrawerRequest = async (url, method, body = null) => {
        const response = await fetch(url, {
            method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body,
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || addErrorText);
        }

        return data;
    };

    const closeMenu = () => {
        if (!navbar || !navToggle) {
            return;
        }

        navbar.classList.remove('is-open');
        page.classList.remove('is-menu-open');
        navToggle.setAttribute('aria-expanded', 'false');
    };

    const closeDropdowns = (currentDropdown = null) => {
        navDropdowns.forEach((dropdown) => {
            if (dropdown === currentDropdown) {
                return;
            }

            dropdown.classList.remove('is-open');
            dropdown.querySelector('.nav-dropdown-button')?.setAttribute('aria-expanded', 'false');
        });
    };

    navDropdowns.forEach((dropdown) => {
        const button = dropdown.querySelector('.nav-dropdown-button');

        button?.addEventListener('click', (event) => {
            event.stopPropagation();

            const isOpen = dropdown.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            closeDropdowns(dropdown);
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.nav-dropdown')) {
            closeDropdowns();
        }
    });

    if (navToggle && navbar) {
        navToggle.addEventListener('click', () => {
            const isOpen = navbar.classList.toggle('is-open');
            page.classList.toggle('is-menu-open', isOpen);
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        navClose?.addEventListener('click', closeMenu);
        navBackdrop?.addEventListener('click', closeMenu);

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu();
                closeDropdowns();
                if (cartDrawerToggle) {
                    cartDrawerToggle.checked = false;
                }
            }
        });

        navPanelLinks.forEach((link) => {
            link.addEventListener('click', () => {
                closeDropdowns();

                if (window.innerWidth <= 900) {
                    closeMenu();
                }
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) {
                closeMenu();
            }

            closeDropdowns();
        });
    }

    cartDrawerItems?.addEventListener('click', async (event) => {
        const button = event.target.closest('button');

        if (!button) {
            return;
        }

        const item = button.closest('[data-cart-drawer-item]');
        const cartKey = item?.dataset.cartKey;

        if (!item || !cartKey) {
            return;
        }

        const quantityEl = item.querySelector('[data-cart-drawer-quantity]');
        const currentQuantity = Number(quantityEl?.textContent || 1);
        const shouldRemove = button.matches('[data-cart-drawer-remove]');
        const nextQuantity = button.matches('[data-cart-drawer-minus]')
            ? currentQuantity - 1
            : currentQuantity + 1;

        try {
            button.disabled = true;
            const data = shouldRemove || nextQuantity < 1
                ? await sendCartDrawerRequest(`/cart/item/${encodeURIComponent(cartKey)}`, 'DELETE')
                : await sendCartDrawerRequest(`/cart/item/${encodeURIComponent(cartKey)}`, 'PATCH', JSON.stringify({ quantity: nextQuantity }));

            updateCartBadge(data.cart_count || 0);
            renderCartDrawer(data);
            showFeedback(data.message || 'Carrito actualizado');
        } catch (error) {
            showFeedback(error.message || addErrorText);
        } finally {
            button.disabled = false;
        }
    });

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
                renderCartDrawer(data);
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
