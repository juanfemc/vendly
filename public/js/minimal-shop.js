document.addEventListener('DOMContentLoaded', () => {
    const showFallback = (image) => {
        const fallback = image.nextElementSibling;
        const isActive = image.classList.contains('is-active');

        image.hidden = true;
        image.classList.remove('is-active');

        if (fallback && fallback.classList.contains('minimal-shop-card-placeholder')) {
            fallback.hidden = false;
        }

        if (fallback && fallback.classList.contains('minimal-shop-hero-fallback')) {
            fallback.hidden = false;
        }

        if (fallback && fallback.classList.contains('minimal-product-placeholder')) {
            fallback.hidden = false;
            fallback.classList.toggle('is-active', isActive);
        }
    };

    const bindImageFallbacks = (root = document) => {
        root.querySelectorAll('.minimal-shop-hero-image, .minimal-shop-card-image, .minimal-product-image').forEach((image) => {
            if (image.dataset.fallbackBound === 'true') {
                return;
            }

            image.dataset.fallbackBound = 'true';
            image.addEventListener('error', () => showFallback(image), { once: true });

            if (image.complete && image.naturalWidth === 0) {
                showFallback(image);
            }
        });
    };

    const page = document.querySelector('.storefront-page--minimal-grid');
    const catalogSection = document.getElementById('catalogo');
    const mobileMenuToggle = document.getElementById('minimalShopMenuToggle');

    const withPartialParam = (href) => {
        const url = new URL(href, window.location.href);

        url.searchParams.set('partial', 'catalogo');
        url.hash = '';

        return url;
    };

    const cleanHistoryUrl = (href) => {
        const url = new URL(href, window.location.href);

        url.searchParams.delete('partial');

        return `${url.pathname}${url.search}${url.hash}`;
    };

    const syncActiveFilters = (href) => {
        const current = new URL(href, window.location.href);
        const selectedCategory = current.searchParams.get('categoria') || '';
        const selectedBadge = current.searchParams.get('etiqueta') || '';

        document.querySelectorAll('[data-minimal-category-link]').forEach((link) => {
            const linkUrl = new URL(link.href, window.location.href);
            const linkCategory = linkUrl.searchParams.get('categoria') || '';
            link.classList.toggle('is-active', !selectedBadge && linkCategory === selectedCategory);
        });

        document.querySelectorAll('[data-minimal-badge-link]').forEach((link) => {
            const linkUrl = new URL(link.href, window.location.href);
            link.classList.toggle('is-active', (linkUrl.searchParams.get('etiqueta') || '') === selectedBadge);
        });
    };

    const closeMobileMenu = () => {
        if (mobileMenuToggle) {
            mobileMenuToggle.checked = false;
        }
    };

    const scrollToCatalog = () => {
        if (!catalogSection) {
            return;
        }

        const navHeight = document.querySelector('.minimal-shop-nav')?.offsetHeight || 0;
        const targetTop = catalogSection.getBoundingClientRect().top + window.scrollY - navHeight - 14;

        window.scrollTo({
            top: Math.max(0, targetTop),
            behavior: 'smooth',
        });
    };

    const replaceCatalog = async (href, shouldPushState = true) => {
        const currentShell = document.querySelector('[data-minimal-catalog-shell]');

        if (!currentShell) {
            window.location.href = href;
            return;
        }

        currentShell.classList.add('is-loading');

        const response = await fetch(withPartialParam(href), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        });

        if (!response.ok) {
            throw new Error('No se pudo cargar el catalogo.');
        }

        const html = await response.text();
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const nextShell = template.content.querySelector('[data-minimal-catalog-shell]');

        if (!nextShell) {
            throw new Error('Respuesta de catalogo invalida.');
        }

        currentShell.replaceWith(nextShell);
        bindImageFallbacks(nextShell);
        syncActiveFilters(href);

        if (shouldPushState) {
            window.history.pushState({ minimalCatalogUrl: href }, '', cleanHistoryUrl(href));
        }

        scrollToCatalog();
    };

    document.addEventListener('click', async (event) => {
        if (!page || !catalogSection) {
            return;
        }

        const link = event.target.closest('[data-minimal-category-link], [data-minimal-badge-link], [data-minimal-catalog-shell] .minimal-shop-pagination a');

        if (!link) {
            return;
        }

        event.preventDefault();

        if (link.closest('.minimal-shop-mobile-menu')) {
            closeMobileMenu();
        }

        try {
            await replaceCatalog(link.href);
        } catch (error) {
            window.location.href = link.href;
        }
    });

    window.addEventListener('popstate', async () => {
        if (!page || !catalogSection) {
            return;
        }

        try {
            await replaceCatalog(window.location.href, false);
        } catch (error) {
            window.location.reload();
        }
    });

    bindImageFallbacks();
});
