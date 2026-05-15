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

    document.querySelectorAll('.minimal-shop-hero-image').forEach((image) => {
        image.addEventListener('error', () => showFallback(image), { once: true });

        if (image.complete && image.naturalWidth === 0) {
            showFallback(image);
        }
    });

    document.querySelectorAll('.minimal-shop-card-image, .minimal-product-image').forEach((image) => {
        image.addEventListener('error', () => showFallback(image), { once: true });

        if (image.complete && image.naturalWidth === 0) {
            showFallback(image);
        }
    });
});
