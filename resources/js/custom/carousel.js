// Mouse drag-to-scroll for .carousel-row elements on desktop.
// Native overflow-x scroll handles horizontal Magic Mouse swipes.

let dragEl = null;
let startX = 0;
let scrollLeft = 0;
let hasDragged = false;

function initCarouselDrag() {
    document.querySelectorAll('.carousel-row').forEach(el => {
        if (el.dataset.dragInit) return;
        el.dataset.dragInit = '1';
        el.style.cursor = 'grab';

        el.addEventListener('mousedown', e => {
            if (e.button !== 0) return;
            e.preventDefault(); // prevent native link/image drag hijacking mousemove
            dragEl = el;
            hasDragged = false;
            startX = e.pageX;
            scrollLeft = el.scrollLeft;
            el.style.cursor = 'grabbing';
        });
    });
}

document.addEventListener('mousemove', e => {
    if (!dragEl) return;
    const dx = e.pageX - startX;
    dragEl.scrollLeft = scrollLeft - dx;
    if (Math.abs(dx) > 4) hasDragged = true;
});

document.addEventListener('mouseup', () => {
    if (!dragEl) return;
    dragEl.style.cursor = 'grab';
    dragEl = null;

    if (hasDragged) {
        // Block the click that the browser fires immediately after mouseup
        const blocker = e => {
            e.preventDefault();
            e.stopImmediatePropagation();
            document.removeEventListener('click', blocker, true);
        };
        document.addEventListener('click', blocker, true);
        // Safety fallback: remove blocker if no click fires within 200ms
        setTimeout(() => document.removeEventListener('click', blocker, true), 200);
    }
});

document.addEventListener('mouseleave', () => {
    if (!dragEl) return;
    dragEl.style.cursor = 'grab';
    dragEl = null;
});

document.addEventListener('DOMContentLoaded', initCarouselDrag);
