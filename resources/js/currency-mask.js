/**
 * ManERP Currency Masking — Alpine.js Plugin
 *
 * Provides:
 *   1. Alpine directive  `x-currency`  → real-time input masking
 *   2. Global helper     `ManERP.formatCurrency(value)`
 *   3. Global helper     `ManERP.parseCurrency(string)`
 *
 * The configuration is read from the <meta name="currency-config"> tag
 * injected by the Blade layout (symbol, thousandSep, decimalSep, decimals).
 */

// ── Read currency config from <meta> tag ──────────────────────────
function readConfig() {
    const el = document.querySelector('meta[name="currency-config"]');
    if (el) {
        try {
            return JSON.parse(el.content);
        } catch (_) { /* fallback below */ }
    }
    return { symbol: 'Rp', thousandSep: '.', decimalSep: ',', decimals: 0 };
}

const cfg = readConfig();

// ── Helpers ───────────────────────────────────────────────────────
function escapeRegex(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Parse a formatted currency string back to a raw numeric string.
 * "Rp 1.234.567,89" → "1234567.89"
 */
function parseCurrency(str) {
    if (str == null || str === '') return '';
    let s = String(str);
    // Remove currency symbol
    s = s.replace(new RegExp(escapeRegex(cfg.symbol), 'g'), '');
    // Remove thousand separators
    s = s.replace(new RegExp(escapeRegex(cfg.thousandSep), 'g'), '');
    // Normalise decimal separator to dot
    if (cfg.decimalSep !== '.') {
        s = s.replace(new RegExp(escapeRegex(cfg.decimalSep), 'g'), '.');
    }
    // Strip remaining non-numeric chars (except minus and dot)
    s = s.replace(/[^\d.\-]/g, '').trim();
    return s === '' ? '' : s;
}

/**
 * Format a raw number with thousand separators.
 * 1234567.89 → "1.234.567,89"  (no currency symbol)
 */
function formatNumber(value) {
    const num = parseFloat(value);
    if (isNaN(num)) return '';
    const fixed = num.toFixed(cfg.decimals);
    const [intPart, decPart] = fixed.split('.');
    const neg = intPart.startsWith('-');
    const absInt = neg ? intPart.slice(1) : intPart;
    const grouped = absInt.replace(/\B(?=(\d{3})+(?!\d))/g, cfg.thousandSep);
    let result = (neg ? '-' : '') + grouped;
    if (cfg.decimals > 0 && decPart !== undefined) {
        result += cfg.decimalSep + decPart;
    }
    return result;
}

/**
 * Format a raw number as a full currency string.
 * 1234567 → "Rp 1.234.567"
 */
function formatCurrency(value) {
    const n = formatNumber(value);
    return n === '' ? '' : cfg.symbol + ' ' + n;
}

// ── Expose globally ───────────────────────────────────────────────
window.ManERP = window.ManERP || {};
window.ManERP.formatCurrency = formatCurrency;
window.ManERP.formatNumber   = formatNumber;
window.ManERP.parseCurrency  = parseCurrency;
window.ManERP.currencyConfig = cfg;

// ── Alpine.js Plugin ──────────────────────────────────────────────
export default function currencyMaskPlugin(Alpine) {
    /**
     * Usage:
     *   <input x-currency name="amount" value="1000000">
     *
     * What it does:
     *  - Changes type to "text" with inputmode="decimal"
     *  - Creates a hidden <input> with the original name for form submission
     *  - On input: formats display with thousand separators in real-time
     *  - On focus: shows raw number for easy editing
     *  - On blur:  shows formatted number (with symbol prefix)
     *  - The hidden input always holds the clean numeric value
     */
    Alpine.directive('currency', (el, { modifiers }, { cleanup }) => {
        // ── Setup ──
        const originalName = el.getAttribute('name');
        const initialValue = el.value || el.getAttribute('value') || '';

        // Create hidden input for clean value submission
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        if (originalName) {
            hidden.name = originalName;
            el.removeAttribute('name');
        }
        el.after(hidden);

        // Switch to text input for formatting
        el.type = 'text';
        el.inputMode = 'decimal';

        // ── State helpers ──
        function setRaw(raw) {
            hidden.value = raw;
            // If there's an Alpine x-model, dispatch for sync
            el.dispatchEvent(new CustomEvent('currency-change', { detail: { raw }, bubbles: true }));
        }

        function getRaw() {
            return hidden.value;
        }

        // ── Initialise ──
        const rawInit = parseCurrency(initialValue);
        hidden.value = rawInit;
        el.value = rawInit !== '' ? formatCurrency(rawInit) : '';

        // Set placeholder if none exists
        if (!el.placeholder) {
            el.placeholder = cfg.symbol + ' 0';
        }

        // ── Event handlers ──
        function onFocus() {
            const raw = getRaw();
            el.value = raw;
            // Select all for easy overwrite
            requestAnimationFrame(() => el.select());
        }

        function onBlur() {
            const raw = parseCurrency(el.value);
            setRaw(raw);
            el.value = raw !== '' ? formatCurrency(raw) : '';
        }

        function onInput() {
            // Allow the user to type freely; just clean on-the-fly
            const cursorPos = el.selectionStart;
            const prevLen = el.value.length;
            const raw = parseCurrency(el.value);
            setRaw(raw);

            if (raw !== '') {
                const formatted = formatNumber(raw);
                el.value = formatted;
                // Adjust cursor position after formatting
                const diff = formatted.length - prevLen;
                const newPos = Math.max(0, cursorPos + diff);
                el.setSelectionRange(newPos, newPos);
            }
        }

        function onKeydown(e) {
            // Allow: backspace, delete, tab, escape, enter, arrows, home, end
            const allowed = [
                'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End',
            ];
            if (allowed.includes(e.key)) return;

            // Allow Ctrl/Cmd + A/C/V/X
            if ((e.ctrlKey || e.metaKey) && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) return;

            // Allow digits
            if (/^\d$/.test(e.key)) return;

            // Allow minus at start
            if (e.key === '-' && el.selectionStart === 0) return;

            // Allow decimal separator (only one)
            if (cfg.decimals > 0 && (e.key === cfg.decimalSep || e.key === '.')) {
                if (!el.value.includes(cfg.decimalSep) && !el.value.includes('.')) return;
            }

            e.preventDefault();
        }

        el.addEventListener('focus', onFocus);
        el.addEventListener('blur', onBlur);
        el.addEventListener('input', onInput);
        el.addEventListener('keydown', onKeydown);

        cleanup(() => {
            el.removeEventListener('focus', onFocus);
            el.removeEventListener('blur', onBlur);
            el.removeEventListener('input', onInput);
            el.removeEventListener('keydown', onKeydown);
            hidden.remove();
        });
    });
}
