import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

/*
 * Persian/Arabic digit normalization + money formatting for inputs.
 * Ported from ghatehresan-management-system/public/assets/app.js.
 *
 *  - <input data-en-digits>  → fa/ar digits become latin while typing
 *  - <input data-money>      → live thousand separators (٬), stripped on submit
 */
const FA = '۰۱۲۳۴۵۶۷۸۹';
const AR = '٠١٢٣٤٥٦٧٨٩';

function toEn(value) {
    return String(value).replace(/[۰-۹٠-٩]/g, (c) => {
        const i = FA.indexOf(c);
        return i > -1 ? i : AR.indexOf(c);
    });
}

function fmtMoney(value) {
    const digits = toEn(value).replace(/[^\d]/g, '');
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '٬');
}

function unfmtMoney(value) {
    return toEn(value).replace(/[^\d]/g, '');
}

document.querySelectorAll('input[data-en-digits]').forEach((inp) => {
    inp.addEventListener('input', () => {
        inp.value = toEn(inp.value);
    });
});

document.querySelectorAll('input[data-money]').forEach((inp) => {
    if (inp.value) inp.value = fmtMoney(inp.value);
    inp.setAttribute('inputmode', 'numeric');

    inp.addEventListener('input', () => {
        const pos = inp.value.length - inp.selectionStart;
        inp.value = fmtMoney(inp.value);
        const next = inp.value.length - pos;
        try {
            inp.setSelectionRange(next, next);
        } catch {
            /* non-text inputs */
        }
    });

    if (inp.form) {
        inp.form.addEventListener('submit', () => {
            inp.value = unfmtMoney(inp.value);
        });
    }
});
