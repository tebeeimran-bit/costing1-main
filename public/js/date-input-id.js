(function () {
    const nativeValue = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

    function toDisplay(value) {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        return match ? `${match[3]}/${match[2]}/${match[1]}` : String(value || '');
    }

    function toDatabase(value) {
        const text = String(value || '').trim();
        if (text === '') return '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(text)) return text;
        const match = text.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (!match) return null;
        const date = new Date(Date.UTC(Number(match[3]), Number(match[2]) - 1, Number(match[1])));
        if (date.getUTCFullYear() !== Number(match[3]) || date.getUTCMonth() + 1 !== Number(match[2]) || date.getUTCDate() !== Number(match[1])) return null;
        return `${match[3]}-${match[2]}-${match[1]}`;
    }

    function enhance(input) {
        if (!(input instanceof HTMLInputElement) || input.type !== 'date' || input.dataset.dateIdEnhanced === '1') return;
        const initialValue = input.value;
        input.type = 'text';
        input.placeholder = 'dd/mm/yyyy';
        input.inputMode = 'numeric';
        input.autocomplete = 'off';
        input.dataset.dateIdEnhanced = '1';
        nativeValue.set.call(input, toDisplay(initialValue));
        Object.defineProperty(input, 'value', {
            configurable: true,
            get() { return nativeValue.get.call(this); },
            set(value) { nativeValue.set.call(this, toDisplay(value)); },
        });
        input.addEventListener('input', function () {
            const digits = nativeValue.get.call(this).replace(/\D/g, '').slice(0, 8);
            const formatted = digits.length > 4
                ? `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`
                : (digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits);
            nativeValue.set.call(this, formatted);
            this.setCustomValidity('');
        });
    }

    function enhanceAll(root) {
        if (root instanceof HTMLInputElement) enhance(root);
        root.querySelectorAll?.('input[type="date"]').forEach(enhance);
    }

    document.addEventListener('DOMContentLoaded', function () {
        enhanceAll(document);
        new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
            if (node instanceof Element) enhanceAll(node);
        }))).observe(document.body, { childList: true, subtree: true });
    });

    document.addEventListener('submit', function (event) {
        const inputs = [...event.target.querySelectorAll('input[data-date-id-enhanced="1"]')];
        for (const input of inputs) {
            const databaseValue = toDatabase(input.value);
            if (databaseValue === null) {
                event.preventDefault();
                event.stopImmediatePropagation();
                input.setCustomValidity('Gunakan format tanggal dd/mm/yyyy.');
                input.reportValidity();
                input.focus();
                return;
            }
        }
        inputs.forEach(input => {
            const databaseValue = toDatabase(input.value);
            delete input.value;
            nativeValue.set.call(input, databaseValue);
        });
    }, true);
})();
