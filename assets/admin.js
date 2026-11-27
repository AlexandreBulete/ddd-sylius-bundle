// The value beside a star rating follows the pick.
const show = (rating) => {
    const out = rating.querySelector('.ddd-rating__value');
    const picked = rating.querySelector('.ddd-rating__input:checked');
    if (!out) return;
    if (!picked) {
        out.textContent = out.dataset.empty;
        return;
    }
    const stars = Number(picked.value) * (rating.dataset.half === '1' ? 0.5 : 1);
    out.textContent = `${stars.toLocaleString(document.documentElement.lang || undefined)} / ${rating.dataset.max}`;
};

document.addEventListener('change', (event) => {
    const rating = event.target.closest?.('[data-ddd-rating]');
    if (rating) show(rating);
});
