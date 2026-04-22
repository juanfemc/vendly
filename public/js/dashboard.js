(function () {
    const root = document.getElementById('dashboard-banner-slider');

    if (!root) {
        return;
    }

    const slides = root.querySelectorAll('.dashboard-slide');
    const dots = document.querySelectorAll('.dashboard-dot');

    if (!slides.length || slides.length < 2) {
        return;
    }

    let current = 0;

    const showSlide = (index) => {
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === index);
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === index);
        });

        current = index;
    };

    dots.forEach((dot) => {
        dot.addEventListener('click', () => showSlide(Number(dot.dataset.slide)));
    });

    window.setInterval(() => {
        showSlide((current + 1) % slides.length);
    }, 4500);
})();
