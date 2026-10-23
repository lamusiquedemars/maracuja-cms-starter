import { prefersReducedMotion, queryAll } from '../core/dom';

export function initReveal(root = document) {
    const items = queryAll('[data-reveal]', root);

    if (!items.length) {
        return;
    }

    if (prefersReducedMotion()) {
        items.forEach((item) => item.classList.add('is-visible'));

        return;
    }

    if (!('IntersectionObserver' in window)) {
        items.forEach((item) => item.classList.add('is-visible'));

        return;
    }

    document.documentElement.classList.add('reveal-ready');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            const delay = entry.target.dataset.revealDelay;

            if (delay) {
                entry.target.style.transitionDelay = `${delay}ms`;
            }

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, {
        rootMargin: '0px 0px -10% 0px',
        threshold: 0.12,
    });

    items.forEach((item) => observer.observe(item));
}
