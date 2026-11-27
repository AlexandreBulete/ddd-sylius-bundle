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

// Creatable selects (CreatableChoiceType): the UX Autocomplete search, set on
// here rather than in the markup so that the creation dialog is hooked in
// before the controller connects.
const AUTOCOMPLETE = 'symfony--ux-autocomplete--autocomplete';
const NEW_PREFIX = '__new__:';

const dialog = (config, typed) => new Promise((resolve) => {
    const words = typed.trim().split(/\s+/);
    const guess = config.fields.length === 2 && words.length > 1
        ? [words.slice(0, -1).join(' '), words[words.length - 1]]
        : [typed.trim()];
    const box = document.createElement('dialog');
    box.className = 'ddd-dialog';
    const form = document.createElement('form');
    form.method = 'dialog';
    form.innerHTML = `<h3 class="ddd-dialog__title"></h3><div class="ddd-dialog__fields"></div>
        <div class="ddd-dialog__actions"><button type="button" class="btn" value="cancel"></button><button type="submit" class="btn btn-primary" value="ok"></button></div>`;
    form.querySelector('.ddd-dialog__title').textContent = config.title;
    const [cancel, submit] = form.querySelectorAll('button');
    cancel.textContent = config.cancel;
    submit.textContent = config.submit;
    config.fields.forEach((field, i) => {
        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = field.label;
        const input = document.createElement('input');
        input.className = 'form-control';
        input.name = field.name;
        input.value = guess[i] ?? '';
        input.required = i === config.fields.length - 1;
        const row = document.createElement('div');
        row.className = 'mb-3';
        row.append(label, input);
        form.querySelector('.ddd-dialog__fields').append(row);
    });
    box.append(form);
    document.body.append(box);
    const close = (value) => { box.close(); box.remove(); resolve(value); };
    cancel.addEventListener('click', () => close(null));
    box.addEventListener('cancel', () => close(null));
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const values = Object.fromEntries(config.fields.map((f) => [f.name, form.elements[f.name].value.trim()]));
        close(values);
    });
    box.showModal();
    form.querySelector('input')?.select();
});

const creatable = (select) => {
    let config = {};
    try { config = JSON.parse(select.dataset.dddCreatable || '{}'); } catch (e) { /* plain create */ }
    select.addEventListener('autocomplete:pre-connect', (event) => {
        // A typed text the list lacks is offered as a new entry: whole, or
        // through the dialog when the entry has several fields.
        event.detail.options.create = !config.fields ? (typed) => ({ value: NEW_PREFIX + JSON.stringify({ name: typed.trim() }), text: typed.trim() })
            : (typed, callback) => {
                dialog(config, typed).then((values) => {
                    if (!values) return callback();
                    callback({ value: NEW_PREFIX + JSON.stringify(values), text: Object.values(values).filter(Boolean).join(' ') });
                });
            };
        event.detail.options.persist = false;
    });
    select.setAttribute('data-controller', [select.dataset.controller, AUTOCOMPLETE].filter(Boolean).join(' '));
};

document.querySelectorAll('select[data-ddd-creatable]').forEach(creatable);
