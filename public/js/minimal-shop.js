document.addEventListener('DOMContentLoaded', () => {
    const showFallback = (image) => {
        const fallback = image.nextElementSibling;

        image.hidden = true;

        if (fallback && fallback.classList.contains('minimal-shop-card-placeholder')) {
            fallback.hidden = false;
        }

        if (fallback && fallback.classList.contains('minimal-shop-hero-fallback')) {
            fallback.hidden = false;
        }
    };

    document.querySelectorAll('.minimal-shop-hero-image').forEach((image) => {
        image.addEventListener('error', () => showFallback(image), { once: true });

        if (image.complete && image.naturalWidth === 0) {
            showFallback(image);
        }
    });

    document.querySelectorAll('.minimal-shop-card-image').forEach((image) => {
        image.addEventListener('error', () => showFallback(image), { once: true });

        if (image.complete && image.naturalWidth === 0) {
            showFallback(image);
        }
    });
});
