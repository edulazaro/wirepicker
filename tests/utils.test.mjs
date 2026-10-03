import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// The script expects a browser; a few stubs are enough to reach Wirepicker.utils.
const noop = () => {};
const window = { addEventListener: noop };
const document = { readyState: 'complete', addEventListener: noop, querySelectorAll: () => [], documentElement: { lang: 'es' } };
vm.runInNewContext(readFileSync(new URL('../resources/js/wirepicker.js', import.meta.url), 'utf8'), { window, document, Intl, Date, requestAnimationFrame: noop });

const { parse, parseValue, times, toIso, grid, presetRange, presetDate, addMonthsClamped, unavailable, firstAvailable, weekStartFor } = window.Wirepicker.utils;

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

test('a value with a time is read apart, and an impossible time is no time', () => {
    const value = parseValue('2026-10-08 10:30');

    assert.equal(toIso(value.date), '2026-10-08');
    assert.equal(value.time, '10:30');
    assert.equal(parseValue('2026-10-08').time, null);
    assert.equal(parseValue('2026-10-08 25:00').time, null);
    assert.equal(parseValue('2026-02-30 10:00').date, null);
    assert.equal(parseValue('').date, null);
});

test('the times of a day follow the step', () => {
    assert.equal(times(15).length, 96);
    assert.equal(times(15)[1], '00:15');
    assert.equal(times(30).at(-1), '23:30');
    assert.equal(times(0).length, 96);
});

test('a month ahead stays inside the month: 31 January goes to the last of February', () => {
    assert.equal(toIso(addMonthsClamped(new Date(2027, 0, 31), 1)), '2027-02-28');
    assert.equal(toIso(addMonthsClamped(new Date(2028, 0, 31), 1)), '2028-02-29');
    assert.equal(toIso(addMonthsClamped(new Date(2026, 9, 15), 3)), '2027-01-15');
});

test("a single date's shortcuts count from the given day", () => {
    const from = new Date(2027, 0, 31);

    assert.equal(toIso(presetDate('today', from)), '2027-01-31');
    assert.equal(toIso(presetDate('tomorrow', from)), '2027-02-01');
    assert.equal(toIso(presetDate('in-a-week', from)), '2027-02-07');
    assert.equal(toIso(presetDate('in-a-month', from)), '2027-02-28');
    assert.equal(toIso(presetDate('+3d', from)), '2027-02-03');
    assert.equal(toIso(presetDate('+2w', from)), '2027-02-14');
    assert.equal(toIso(presetDate('+1y', from)), '2028-01-31');
    assert.equal(presetDate('this-month', from), null);
});

test('weekends and disabled dates are unavailable, with their reason', () => {
    const rules = { disableWeekends: true, disabled: { '2026-12-08': 'Inmaculada', '2026-12-07': '' } };

    assert.equal(unavailable(new Date(2026, 11, 5), rules), ''); // Saturday
    assert.equal(unavailable(new Date(2026, 11, 8), rules), 'Inmaculada');
    assert.equal(unavailable(new Date(2026, 11, 9), rules), null);
    assert.equal(unavailable(new Date(2026, 11, 5), { disableWeekends: false, disabled: {} }), null);
});

test('the first available day skips weekends and disabled dates', () => {
    const rules = { disableWeekends: true, disabled: { '2026-12-07': '', '2026-12-08': 'Inmaculada' } };

    // Saturday 5 December: Sunday, then Monday 7 and Tuesday 8 are off, Wednesday 9 is not.
    assert.equal(toIso(firstAvailable(new Date(2026, 11, 5), rules)), '2026-12-09');
    assert.equal(toIso(firstAvailable(new Date(2026, 11, 9), rules)), '2026-12-09');
});

test('a time picker offers the times between its min and max, and reads only real times', () => {
    const { timesBetween, parseTime } = window.Wirepicker.utils;

    assert.deepEqual([...timesBetween(60, '06:00', '09:00')], ['06:00', '07:00', '08:00', '09:00']);
    assert.equal(timesBetween(15).length, 96);
    assert.equal(parseTime('09:30'), '09:30');
    assert.equal(parseTime('24:00'), null);
    assert.equal(parseTime('9:30'), null);
});
