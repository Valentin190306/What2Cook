document.addEventListener('DOMContentLoaded', () => {
    function initCarousel(el) {
        const slidesContainer = el.querySelector('.carousel-slides');
        const slides = Array.from(slidesContainer.children);
        const btnPrev = el.querySelector('.carousel-prev');
        const btnNext = el.querySelector('.carousel-next');
        let idx = 0;

        const update = () => {
            slides.forEach((s, i) => {
                s.classList.toggle('active', i === idx);
            });
        };

        if (slides.length === 0) return;
        update();

        btnPrev?.addEventListener('click', (e) => {
            e.preventDefault();
            idx = (idx - 1 + slides.length) % slides.length;
            update();
        });
        btnNext?.addEventListener('click', (e) => {
            e.preventDefault();
            idx = (idx + 1) % slides.length;
            update();
        });
    }

    const carousels = document.querySelectorAll('.plan-carousel');
    carousels.forEach(initCarousel);
});
