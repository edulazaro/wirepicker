![Wirepicker](art/banner.png)

# wirepicker

Framework-agnostic date and date-range picker for Laravel, Livewire and Alpine. Pure CSS, localized through `Intl`. Part of the `wire*` family: themeable through the shared `data-wire-theme` attribute, visually coherent with [wiremodal](https://github.com/edulazaro/wiremodal), [wiretoast](https://github.com/edulazaro/wiretoast), [wirecookies](https://github.com/edulazaro/wirecookies) and [wirebug](https://github.com/edulazaro/wirebug).

One date or a range · shortcuts · keyboard · 0 runtime deps · works with or without Livewire/Alpine.

## Install

```bash
composer require edulazaro/wirepicker
php artisan vendor:publish --tag=wirepicker-assets
```

Add to your layout:

```blade
<link rel="stylesheet" href="{{ asset('vendor/wirepicker/css/wirepicker.css') }}">
<script src="{{ asset('vendor/wirepicker/js/wirepicker.js') }}" defer></script>
```

Or import into your Vite bundle (`resources/css/app.css` + `resources/js/app.js`):

```css
@import "../../vendor/edulazaro/wirepicker/resources/css/wirepicker.css";
```
```js
import '../../vendor/edulazaro/wirepicker/resources/js/wirepicker.js';
```

## One date

```blade
<x-wirepicker wire:model="startsOn" />
```

The value is a `Y-m-d` string, `''` when empty (`Y-m-d H:i` with `time`, below). Any `wire:model` modifier works (`.live`, `.blur`, ...).

## A range

```blade
<x-wirepicker range wire:model.live="period" />
```

```php
public array $period = ['start' => '', 'end' => ''];
```

The first click sets the start, the second one the end, in either order. While the end is still open the days in between light up under the pointer.

### Shortcuts

```blade
<x-wirepicker range wire:model.live="period" :presets="['this-month', 'next-month', 'this-year']" />
```

Available: `today`, `this-week`, `next-7-days`, `this-month`, `next-month`, `last-month`, `this-year`. Unknown names are ignored.

A single date has shortcuts of its own, for due dates and follow-ups:

```blade
<x-wirepicker wire:model="dueOn" :presets="['tomorrow', 'in-a-week', '+3d', '+1m']" />
```

Named ones are `today`, `tomorrow`, `in-a-week` and `in-a-month`; any offset works too, `+3d`, `+2w`, `+1m` or `+1y`, labelled on its own in each language ("In 3 days", "Dentro de 3 días"). A month ahead stays inside the month: from 31 January, `+1m` is the last day of February. The calendar's footer always has "Today" as well.

## Jumping to a month or a year

The calendar's title is a button: it shows the year's twelve months, and the year shows twelve years, so a birth date in 1975 is a few clicks away, not six hundred. Picking a year shows its months, picking a month its days. With `min` or `max`, months and years outside them are disabled.

## Marking busy days

Days that already hold something get a dot under their number, and a title on hover:

```blade
<x-wirepicker wire:model="startsOn" :marked="$busyDays" />
```

`marked` takes a list of `Y-m-d` dates, or `Y-m-d` => a title (or a list of titles, joined): `['2026-10-08' => 'Hotel visit', '2026-10-14' => ['Call', 'Delivery']]`. Changed from Livewire, the calendar picks the new marks up the next time it draws.

## With a time

Add `time` and the calendar shows a time under the days, two for a range ("Start" and "End"):

```blade
<x-wirepicker wire:model="startsAt" time />
<x-wirepicker range wire:model="period" time :step="30" default-time="10:00" />
```

Values become `Y-m-d H:i` (`2026-10-08 10:30`), the start and the end each with its own time. Times are offered every `step` minutes (15 by default), and a day picked before choosing a time gets `default-time` (09:00 by default). The calendar stays open after a day is picked, so the time can be chosen too: it closes with "Done", Escape or a click outside. The field shows the date and the time in the locale's format: `08/10/2026 10:30` in Spanish, `10/08/2026 10:30 AM` in American English.

The time is optional and off by default: without `time`, nothing changes.

## Plain forms

Without Livewire, give it a `name` and, if you like, a `value`:

```blade
<x-wirepicker name="starts_on" value="2026-10-08" />
<x-wirepicker range name="period" :value="['start' => '2026-10-01', 'end' => '2026-10-31']" />
```

A range posts `period[start]` and `period[end]`.

## Alpine and plain JavaScript

Every change fires `wirepicker:change` on the root, bubbling:

```html
<div x-data="{ date: null }" x-on:wirepicker:change="date = $event.detail.value">
    <x-wirepicker />
</div>
```

`detail` holds `value` (a string, or `{ start, end }` for a range), `start` and `end`.

`Wirepicker.refresh()` repaints every picker after you change its fields yourself; `Wirepicker.close()` closes the open calendar. After each Livewire request this happens on its own.

## Options

| Prop | Default | |
|---|---|---|
| `range` | `false` | Two dates instead of one |
| `presets` | `[]` | Shortcuts beside the calendar: a range's, or a single date's |
| `marked` | none | Days with a dot: Y-m-d, or Y-m-d => title |
| `placeholder` | translated | Text while empty |
| `min` / `max` | none | First and last date that can be picked, `Y-m-d` |
| `clearable` | `true` | A button to empty it |
| `name` | none | Field name for a plain form |
| `value` | none | Initial value outside Livewire |
| `size` | `md` | `sm` for toolbars |
| `locale` | app locale | Months, weekdays, date format and first day of the week |
| `week-start` | from the locale | `0` Sunday to `6` Saturday |
| `id` | none | Set on the button, for `<label for="...">` |
| `time` | `false` | A time too: values become `Y-m-d H:i` |
| `step` | `15` | With `time`, minutes between the times offered |
| `default-time` | `09:00` | With `time`, the time a day gets before one is chosen |

Any other attribute (`class`, `data-*`) lands on the root.

## Languages

Months, weekdays and the date format come from `Intl`, so every language the browser knows works with no locale files: `es` shows `08/10/2026` and starts the week on Monday, `en-US` shows `10/08/2026` and starts on Sunday.

The few words of its own (placeholder, Today, Clear, the shortcuts, and Time, Start, End and Done) ship in English and Spanish. Publish them to add or change languages:

```bash
php artisan vendor:publish --tag=wirepicker-lang
```

## Themes

Pick the theme once on `<html>` (shared with the rest of the family):

```html
<html data-wire-theme="studio">
```

The family's themes:

| Theme | Vibe |
|---|---|
| default | Neutral light/dark, follows system |
| `soft` | Tinted with the accent |
| `glass` | Frosted backdrop blur |
| `gradient` | The calendar on a vivid gradient, white text |
| `neon` | Dark, glowing accent, mono font |
| `minimal` | No border, left accent stripe |
| `claude` | Warm minimal, Anthropic-inspired |
| `chatgpt` | Clean neutral, round days |
| `studio` | Gray-900, sharp corners, deep shadow |
| `synthwave` | Retro 80s purple/magenta neon |
| `megaflow` | Flowbite-style: clean white card |
| `brutalist` | Black border, hard offset shadow |

Dark mode: set `data-wire-theme-mode="dark"` or add the `.dark` class to an ancestor. A theme set on a container instead of `<html>` works too: the calendar opens on `<body>` and takes the picker's theme and mode with it.

## Custom theme

Everything is a CSS variable. The `--wire-*` tokens are shared with wiremodal and wiretoast, so a theme written for them already reaches the picker; `--wp-*` are its own:

```css
:root {
    --wire-accent: #0f172a;
    --wire-font: "Inter", sans-serif;

    --wp-trigger-radius: 6px;
    --wp-trigger-font-size: 14px;
    --wp-radius: 8px;
    --wp-day-size: 34px;
}
```

| Token | |
|---|---|
| `--wp-trigger-bg`, `--wp-trigger-text`, `--wp-trigger-placeholder`, `--wp-trigger-border`, `--wp-trigger-focus`, `--wp-trigger-icon` | The field's colours |
| `--wp-trigger-radius`, `--wp-trigger-padding`, `--wp-trigger-padding-sm`, `--wp-trigger-font-size` | The field's shape |
| `--wp-bg`, `--wp-text`, `--wp-text-muted`, `--wp-border`, `--wp-shadow` | The calendar's colours |
| `--wp-accent`, `--wp-accent-text`, `--wp-range`, `--wp-hover` | Selected days, the days in a range, hover |
| `--wp-radius`, `--wp-day-size`, `--wp-day-radius`, `--wp-font`, `--wp-font-size` | The calendar's shape |
| `--wp-z-index` | Above wiremodal by default, so a picker inside a modal opens over it |

Dark mode follows `prefers-color-scheme`, a `.dark` ancestor or `data-wire-theme-mode="dark"`, as the rest of the family does.

## Anatomy

```
.wp-picker              root, [data-wirepicker]
  input[data-wp-input]  the value: "start", and "end" for a range
  .wp-trigger           the field that opens the calendar
  .wp-clear             empties it

.wp-popover             one calendar on <body>, for whichever picker is open
  .wp-presets           shortcuts
  .wp-calendar
    .wp-header          month, previous and next
    .wp-weekdays
    .wp-days            .wp-day (.wp-today, .wp-selected, .wp-start, .wp-end, .wp-in-range, .wp-outside)
    .wp-footer          Today, Clear
```

The calendar lives on `<body>` so no table, card or modal around the picker can clip it.

## Keyboard

Arrows move a day or a week, Page Up and Page Down a month, Enter picks, Escape closes and gives the focus back to the field.

## Tests

```bash
composer install && vendor/bin/phpunit   # the Blade component
npm test                                 # the date arithmetic, with node --test
```

## Sponsors

Wirepicker is supported by the following sponsors. Thank you for keeping it growing:

<p>
  <a href="https://kenodo.com"><img src="art/logo-kenodo.png" width="24" alt="Kenodo"></a>&nbsp;<a href="https://kenodo.com">Kenodo</a>&nbsp;&nbsp;&nbsp;&nbsp;
  <a href="https://andorradev.com"><img src="art/logo-andorradev.png" width="24" alt="AndorraDev"></a>&nbsp;<a href="https://andorradev.com">AndorraDev</a>
</p>

## Author

Created by [Edu Lazaro](https://edulazaro.com)

## License

Wirepicker is open-sourced software licensed under the [MIT license](LICENSE).
