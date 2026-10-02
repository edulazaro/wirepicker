/*!
 * wirepicker - framework-agnostic date and date-range picker
 *
 * Markup comes from the <x-wirepicker> Blade component: a [data-wirepicker] root with hidden
 * inputs ([data-wp-input="start"], and "end" for a range) holding Y-m-d values, a trigger
 * button and an optional clear button. This file draws the calendar in one popover on
 * <body>, so no table, card or modal around the picker can clip it.
 *
 * Writing a value sets the hidden inputs and fires `input` and `change` on them, which is all
 * wire:model and plain forms need; the root also fires `wirepicker:change` with
 * { value, start, end } for Alpine or vanilla listeners.
 *
 * Values written from outside (a Livewire property reset, a form reset) are picked up after
 * every Livewire request, or by calling Wirepicker.refresh().
 *
 * Languages come from Intl: months, weekdays, the date format and the first day of the week
 * all follow data-wp-locale, with no locale files.
 */
(function () {
    'use strict';

    if (window.Wirepicker) {
        return;
    }

    /* ---------------------------------------------------------------
       Dates: plain local dates, never times, always Y-m-d strings out
       --------------------------------------------------------------- */

    const pad = (n) => String(n).padStart(2, '0');

    const toIso = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

    function parse(value) {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');

        if (!match) {
            return null;
        }

        const date = new Date(+match[1], +match[2] - 1, +match[3]);

        return date.getMonth() === +match[2] - 1 ? date : null;
    }

    const addDays = (date, days) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);

    const addMonths = (date, months) => new Date(date.getFullYear(), date.getMonth() + months, 1);

    const today = () => {
        const now = new Date();

        return new Date(now.getFullYear(), now.getMonth(), now.getDate());
    };

    /** The first day of the week for a locale: 0 Sunday … 6 Saturday. */
    function weekStartFor(locale) {
        try {
            const info = new Intl.Locale(locale);
            const firstDay = (info.getWeekInfo ? info.getWeekInfo() : info.weekInfo)?.firstDay;

            if (firstDay) {
                return firstDay % 7;
            }
        } catch (e) {
            // An unknown locale falls through to the default.
        }

        return /^en-(US|CA)$|^(ja|ko|zh|he|pt-BR)/.test(locale) ? 0 : 1;
    }

    /** Six weeks starting on the week day that contains the month's first day. */
    function grid(year, month, weekStart) {
        const first = new Date(year, month, 1);
        const start = addDays(first, -((first.getDay() - weekStart + 7) % 7));

        return Array.from({ length: 42 }, (_, i) => addDays(start, i));
    }

    /** A preset's [start, end] as dates, counted from `from`. */
    function presetRange(preset, weekStart, from = today()) {
        switch (preset) {
            case 'today':
                return [from, from];
            case 'this-week': {
                const start = addDays(from, -((from.getDay() - weekStart + 7) % 7));
                return [start, addDays(start, 6)];
            }
            case 'next-7-days':
                return [from, addDays(from, 6)];
            case 'this-month':
                return [new Date(from.getFullYear(), from.getMonth(), 1), new Date(from.getFullYear(), from.getMonth() + 1, 0)];
            case 'next-month':
                return [new Date(from.getFullYear(), from.getMonth() + 1, 1), new Date(from.getFullYear(), from.getMonth() + 2, 0)];
            case 'last-month':
                return [new Date(from.getFullYear(), from.getMonth() - 1, 1), new Date(from.getFullYear(), from.getMonth(), 0)];
            case 'this-year':
                return [new Date(from.getFullYear(), 0, 1), new Date(from.getFullYear(), 11, 31)];
            default:
                return null;
        }
    }

    const capitalize = (text) => text.charAt(0).toUpperCase() + text.slice(1);

    /* ---------------------------------------------------------------
       One picker: reads and writes its root's hidden inputs
       --------------------------------------------------------------- */

    function config(root) {
        const locale = root.dataset.wpLocale || document.documentElement.lang || 'en';
        let labels = {};

        try {
            labels = JSON.parse(root.dataset.wpLabels || '{}');
        } catch (e) {
            // Missing labels only cost the wording.
        }

        return {
            locale,
            range: root.dataset.wpMode === 'range',
            weekStart: root.dataset.wpWeekStart !== undefined ? +root.dataset.wpWeekStart : weekStartFor(locale),
            min: parse(root.dataset.wpMin),
            max: parse(root.dataset.wpMax),
            clearable: root.hasAttribute('data-wp-clearable'),
            labels,
        };
    }

    const input = (root, part) => root.querySelector(`[data-wp-input="${part}"]`);

    function read(root) {
        return {
            start: parse(input(root, 'start')?.value),
            end: parse(input(root, 'end')?.value),
        };
    }

    function format(date, locale) {
        return new Intl.DateTimeFormat(locale, { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
    }

    /** Puts the current value on the trigger, and shows the clear button when there is one. */
    function paint(root) {
        const { locale, range, labels } = config(root);
        const { start, end } = read(root);
        const display = root.querySelector('[data-wp-display]');
        const clear = root.querySelector('[data-wp-clear]');

        let text = labels.placeholder || '';

        if (start && range) {
            text = `${format(start, locale)} – ${end ? format(end, locale) : '…'}`;
        } else if (start) {
            text = format(start, locale);
        }

        if (display) {
            display.textContent = text;
            display.toggleAttribute('data-wp-empty', !start);
        }

        if (clear) {
            clear.hidden = !start;
        }
    }

    function write(root, start, end) {
        const { range } = config(root);
        const parts = range ? { start, end } : { start };

        Object.entries(parts).forEach(([part, date]) => {
            const field = input(root, part);

            if (field) {
                field.value = date ? toIso(date) : '';
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        paint(root);

        root.dispatchEvent(new CustomEvent('wirepicker:change', {
            bubbles: true,
            detail: {
                value: range ? { start: start ? toIso(start) : null, end: end ? toIso(end) : null } : (start ? toIso(start) : null),
                start: start ? toIso(start) : null,
                end: end ? toIso(end) : null,
            },
        }));
    }

    /* ---------------------------------------------------------------
       The popover: one on <body>, drawn for whichever picker is open
       --------------------------------------------------------------- */

    let popover = null;
    let open = null; // { root, view: Date (1st of month), focus: Date, anchor: Date|null, hover: Date|null }

    function element() {
        if (!popover) {
            popover = document.createElement('div');
            popover.className = 'wp-popover';
            popover.setAttribute('role', 'dialog');
            popover.hidden = true;
            popover.addEventListener('click', onPopoverClick);
            popover.addEventListener('mouseover', onPopoverHover);
            document.body.appendChild(popover);
        }

        return popover;
    }

    function inRange(date, cfg) {
        return (!cfg.min || date >= cfg.min) && (!cfg.max || date <= cfg.max);
    }

    function render() {
        if (!open) {
            return;
        }

        const cfg = config(open.root);
        const { start, end } = read(open.root);
        const selStart = open.anchor || start;
        const selEnd = open.anchor ? (open.hover && open.hover >= open.anchor ? open.hover : null) : end;
        const todayIso = toIso(today());
        const month = open.view.getMonth();
        const title = capitalize(new Intl.DateTimeFormat(cfg.locale, { month: 'long', year: 'numeric' }).format(open.view));
        const weekdays = Array.from({ length: 7 }, (_, i) => {
            // 2024-01-07 was a Sunday: offset from it to name each column.
            const day = new Date(2024, 0, 7 + ((cfg.weekStart + i) % 7));
            return capitalize(new Intl.DateTimeFormat(cfg.locale, { weekday: 'short' }).format(day).replace('.', ''));
        });

        const days = grid(open.view.getFullYear(), month, cfg.weekStart).map((date) => {
            const iso = toIso(date);
            const classes = ['wp-day'];
            const isStart = selStart && iso === toIso(selStart);
            const isEnd = selEnd && iso === toIso(selEnd);

            if (date.getMonth() !== month) classes.push('wp-outside');
            if (iso === todayIso && date.getMonth() === month) classes.push('wp-today');
            if (isStart || isEnd || (!cfg.range && isStart)) classes.push('wp-selected');
            if (cfg.range && isStart) classes.push('wp-start');
            if (cfg.range && isEnd) classes.push('wp-end');
            if (cfg.range && selStart && selEnd && date > selStart && date < selEnd) classes.push('wp-in-range');
            if (open.focus && iso === toIso(open.focus)) classes.push('wp-focus');

            const disabled = !inRange(date, cfg);

            return `<button type="button" class="${classes.join(' ')}" data-wp-day="${iso}"${disabled ? ' disabled' : ''} tabindex="-1" aria-pressed="${isStart || isEnd ? 'true' : 'false'}">${date.getDate()}</button>`;
        }).join('');

        const presets = cfg.range ? Object.entries(cfg.labels.presets || {}) : [];
        const escape = (text) => String(text).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

        element().innerHTML = `
            ${presets.length ? `<div class="wp-presets">${presets.map(([key, label]) => `<button type="button" class="wp-preset" data-wp-preset="${key}">${escape(label)}</button>`).join('')}</div>` : ''}
            <div class="wp-calendar">
                <div class="wp-header">
                    <button type="button" class="wp-nav" data-wp-nav="-1" aria-label="${escape(cfg.labels.previous || '')}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg></button>
                    <span class="wp-title" aria-live="polite">${escape(title)}</span>
                    <button type="button" class="wp-nav" data-wp-nav="1" aria-label="${escape(cfg.labels.next || '')}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg></button>
                </div>
                <div class="wp-weekdays">${weekdays.map((name) => `<span>${escape(name)}</span>`).join('')}</div>
                <div class="wp-days" role="grid">${days}</div>
                <div class="wp-footer">
                    <button type="button" class="wp-link" data-wp-today>${escape(cfg.labels.today || '')}</button>
                    ${cfg.clearable ? `<button type="button" class="wp-link" data-wp-clear-all>${escape(cfg.labels.clear || '')}</button>` : ''}
                </div>
            </div>`;

        element().dataset.wpSize = open.root.dataset.wpSize || 'md';
        position();
    }

    function position() {
        if (!open || !popover) {
            return;
        }

        const trigger = open.root.getBoundingClientRect();
        const box = popover.getBoundingClientRect();
        const gap = 6;
        const below = trigger.bottom + gap;
        const top = below + box.height > window.innerHeight - 8 && trigger.top - gap - box.height > 8
            ? trigger.top - gap - box.height
            : below;
        const left = Math.min(Math.max(trigger.left, 8), window.innerWidth - box.width - 8);

        popover.style.top = `${Math.round(top)}px`;
        popover.style.left = `${Math.round(left)}px`;
    }

    function show(root) {
        const { start } = read(root);
        const focus = start || today();

        open = { root, view: new Date(focus.getFullYear(), focus.getMonth(), 1), focus, anchor: null, hover: null };
        element().hidden = false;
        root.querySelector('[data-wp-trigger]')?.setAttribute('aria-expanded', 'true');
        render();
        focusDay();
    }

    function hide(returnFocus = false) {
        if (!open) {
            return;
        }

        const trigger = open.root.querySelector('[data-wp-trigger]');
        trigger?.setAttribute('aria-expanded', 'false');
        open = null;

        if (popover) {
            popover.hidden = true;
            popover.innerHTML = '';
        }

        if (returnFocus) {
            trigger?.focus();
        }
    }

    function focusDay() {
        popover?.querySelector('.wp-focus')?.focus({ preventScroll: true });
    }

    /** A day was chosen: a single date closes; a range waits for its second click. */
    function choose(date) {
        const cfg = config(open.root);

        if (!inRange(date, cfg)) {
            return;
        }

        if (!cfg.range) {
            write(open.root, date, null);
            hide(true);
            return;
        }

        if (!open.anchor) {
            open.anchor = date;
            open.focus = date;
            render();
            focusDay();
            return;
        }

        const [start, end] = date < open.anchor ? [date, open.anchor] : [open.anchor, date];
        write(open.root, start, end);
        hide(true);
    }

    function onPopoverClick(event) {
        const day = event.target.closest('[data-wp-day]');
        const nav = event.target.closest('[data-wp-nav]');
        const preset = event.target.closest('[data-wp-preset]');

        if (day && !day.disabled) {
            choose(parse(day.dataset.wpDay));
        } else if (nav) {
            open.view = addMonths(open.view, +nav.dataset.wpNav);
            open.focus = open.view;
            render();
        } else if (preset) {
            const [start, end] = presetRange(preset.dataset.wpPreset, config(open.root).weekStart);
            write(open.root, start, end);
            hide(true);
        } else if (event.target.closest('[data-wp-today]')) {
            const cfg = config(open.root);

            if (cfg.range) {
                open.view = new Date(today().getFullYear(), today().getMonth(), 1);
                open.focus = today();
                render();
                focusDay();
            } else {
                choose(today());
            }
        } else if (event.target.closest('[data-wp-clear-all]')) {
            write(open.root, null, null);
            hide(true);
        }
    }

    function onPopoverHover(event) {
        const day = event.target.closest('[data-wp-day]');

        if (open && open.anchor && day) {
            const hover = parse(day.dataset.wpDay);

            if (!open.hover || toIso(open.hover) !== toIso(hover)) {
                open.hover = hover;
                open.focus = hover;
                render();
                // Redrawing replaced the focused button: give the keyboard its place back.
                focusDay();
            }
        }
    }

    function onKeydown(event) {
        if (!open) {
            return;
        }

        const moves = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };

        if (event.key === 'Escape') {
            event.preventDefault();
            hide(true);
        } else if (event.key in moves && popover.contains(document.activeElement)) {
            event.preventDefault();
            open.focus = addDays(open.focus, moves[event.key]);
            if (open.anchor) open.hover = open.focus;
            open.view = new Date(open.focus.getFullYear(), open.focus.getMonth(), 1);
            render();
            focusDay();
        } else if ((event.key === 'PageUp' || event.key === 'PageDown') && popover.contains(document.activeElement)) {
            event.preventDefault();
            const step = event.key === 'PageUp' ? -1 : 1;
            open.focus = new Date(open.focus.getFullYear(), open.focus.getMonth() + step, Math.min(open.focus.getDate(), 28));
            open.view = new Date(open.focus.getFullYear(), open.focus.getMonth(), 1);
            render();
            focusDay();
        }
    }

    /* ---------------------------------------------------------------
       Wiring: delegated, so pickers added later need no setup
       --------------------------------------------------------------- */

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-wp-trigger]');
        const clear = event.target.closest('[data-wp-clear]');

        if (trigger) {
            const root = trigger.closest('[data-wirepicker]');
            open && open.root === root ? hide() : (hide(), show(root));
        } else if (clear) {
            hide();
            write(clear.closest('[data-wirepicker]'), null, null);
        } else if (open && !event.composedPath().includes(popover)) {
            // composedPath, not contains(): a click that redraws the calendar has already
            // detached its own button by the time it reaches the document.
            hide();
        }
    });

    document.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', position);
    window.addEventListener('scroll', position, true);

    function refresh(scope = document) {
        scope.querySelectorAll('[data-wirepicker]').forEach(paint);

        if (open && !document.body.contains(open.root)) {
            hide();
        }
    }

    // Livewire writes wire:model inputs after each request (a filter reset, say): repaint then.
    document.addEventListener('livewire:init', () => {
        // After the morph and after Livewire has written the bound inputs: next frame, then a tick.
        window.Livewire.hook('commit', ({ succeed }) => succeed(() => requestAnimationFrame(() => setTimeout(() => refresh()))));
    });
    document.addEventListener('livewire:initialized', () => refresh());
    document.addEventListener('livewire:navigated', () => refresh());

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => refresh());
    } else {
        refresh();
    }

    window.Wirepicker = {
        refresh,
        close: hide,
        // The date arithmetic, exposed for tests and for anyone building on it.
        utils: { parse, toIso, grid, presetRange, weekStartFor },
    };
})();
