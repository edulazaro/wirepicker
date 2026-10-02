import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// The script expects a browser; a few stubs are enough to reach Wirepicker.utils.
const noop = () => {};
const window = { addEventListener: noop };
const document = { readyState: 'complete', addEventListener: noop, querySelectorAll: () => [], documentElement: { lang: 'es' } };
vm.runInNewContext(readFileSync(new URL('../resources/js/wirepicker.js', import.meta.url), 'utf8'), { window, document, Intl, Date, requestAnimationFrame: noop });

const { parse, toIso, grid, presetRange, weekStartFor } = window.Wirepicker.utils;

test('parses only real Y-m-d dates', () => {
    assert.equal(toIso(parse('2026-10-08')), '2026-10-08');
    assert.equal(parse('2026-02-30'), null);
    assert.equal(parse('08/10/2026'), null);
    assert.equal(parse(''), null);
});

test('a month grid is six weeks from the chosen first weekday', () => {
    const monday = grid(2026, 9, 1);
    assert.equal(monday.length, 42);
    assert.equal(toIso(monday[0]), '2026-09-28');
    assert.equal(monday[0].getDay(), 1);

    const sunday = grid(2026, 9, 0);
    assert.equal(toIso(sunday[0]), '2026-09-27');
});

test('presets count from the given day', () => {
    const from = new Date(2026, 9, 2); // Friday 2 October 2026
    // Array.from: arrays made inside the vm have another prototype, which deepEqual tells apart.
    const iso = (range) => Array.from(range, toIso);

    assert.deepEqual(iso(presetRange('this-month', 1, from)), ['2026-10-01', '2026-10-31']);
    assert.deepEqual(iso(presetRange('next-month', 1, from)), ['2026-11-01', '2026-11-30']);
    assert.deepEqual(iso(presetRange('last-month', 1, from)), ['2026-09-01', '2026-09-30']);
    assert.deepEqual(iso(presetRange('this-week', 1, from)), ['2026-09-28', '2026-10-04']);
    assert.deepEqual(iso(presetRange('next-7-days', 1, from)), ['2026-10-02', '2026-10-08']);
    assert.deepEqual(iso(presetRange('this-year', 1, from)), ['2026-01-01', '2026-12-31']);
    assert.equal(presetRange('nonsense', 1, from), null);
});

test('the first weekday follows the locale', () => {
    assert.equal(weekStartFor('es'), 1);
    assert.equal(weekStartFor('en-US'), 0);
});
