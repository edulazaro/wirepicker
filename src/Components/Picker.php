<?php

namespace EduLazaro\Wirepicker\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

/**
 * A date, or a date range, picked from a calendar.
 *
 * The value lives in hidden inputs, so the picker binds like any input: `wire:model` (a date
 * as `Y-m-d`, or `Y-m-d H:i` with `time`; a range as an array with `start` and `end`), a
 * plain form field (`name`), or
 * Alpine through the `wirepicker:change` event. The calendar itself is drawn by
 * wirepicker.js, which keeps the package free of any CSS or JS framework.
 */
class Picker extends Component
{
    /** A range's shortcuts. */
    public const PRESETS = ['today', 'this-week', 'next-7-days', 'this-month', 'next-month', 'last-month', 'this-year'];

    /** A single date's shortcuts, besides offsets like `+3d`, `+2w`, `+1m` or `+1y`. */
    public const SINGLE_PRESETS = ['today', 'tomorrow', 'in-a-week', 'in-a-month'];

    /** An offset shortcut of a single date: a number of days, weeks, months or years ahead. */
    public const OFFSET = '/^\+([1-9]\d{0,2})([dwmy])$/';

    /** @var array<string, string> Y-m-d => what the day holds ('' for a bare mark). */
    public array $marked = [];

    public string $locale;

    /** @var list<string> */
    public array $presets;

    /**
     * @param  bool  $range  Two dates, start and end, instead of one.
     * @param  list<string>  $presets  Shortcuts beside the calendar (range only): any of PRESETS.
     * @param  string|null  $placeholder  Shown while empty.
     * @param  string|null  $min  The first date that can be picked (Y-m-d).
     * @param  string|null  $max  The last date that can be picked (Y-m-d).
     * @param  bool  $clearable  A button to empty it.
     * @param  string|null  $name  Field name for a plain form; a range posts name[start] and name[end].
     * @param  string|array<string, string|null>|null  $value  Initial value outside Livewire.
     * @param  string  $size  `sm` or `md`.
     * @param  string|null  $locale  Language of months, days and formats; the app's by default.
     * @param  int|null  $weekStart  0 Sunday … 6 Saturday; the locale's by default.
     * @param  string|null  $id  For a <label for="…">: set on the button that opens it.
     * @param  bool  $time  A time too: values become `Y-m-d H:i`, chosen under the days.
     * @param  int  $step  With `time`, the minutes between the times offered.
     * @param  string|null  $defaultTime  With `time`, the time a day gets before one is chosen (H:i, 09:00 by default).
     * @param  array<int|string, mixed>|null  $marked  Days that already hold something, marked with a dot: a list of
     *                                               Y-m-d, or Y-m-d => a title (or a list of them) shown on hover.
     */
    public function __construct(
        public bool $range = false,
        array $presets = [],
        public ?string $placeholder = null,
        public ?string $min = null,
        public ?string $max = null,
        public bool $clearable = true,
        public ?string $name = null,
        public string|array|null $value = null,
        public string $size = 'md',
        ?string $locale = null,
        public ?int $weekStart = null,
        public ?string $id = null,
        public bool $time = false,
        public int $step = 15,
        public ?string $defaultTime = null,
        ?array $marked = null,
    ) {
        $this->locale = str_replace('_', '-', $locale ?? app()->getLocale());
        // Each mode keeps its own shortcuts; an unknown one is dropped, not drawn dead.
        $this->presets = array_values(array_filter($presets, fn ($preset) => is_string($preset) && ($range
            ? in_array($preset, self::PRESETS, true)
            : in_array($preset, self::SINGLE_PRESETS, true) || preg_match(self::OFFSET, $preset))));
        $this->marked = self::marks($marked ?? []);
    }

    /**
     * `marked` as Y-m-d => title, whatever shape it came in; anything that is not a date is
     * left out.
     *
     * @param  array<int|string, mixed>  $marked
     * @return array<string, string>
     */
    private static function marks(array $marked): array
    {
        $marks = [];

        foreach ($marked as $key => $title) {
            [$date, $title] = is_int($key) ? [$title, ''] : [$key, $title];

            if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $marks[$date] = implode(' · ', array_filter(array_map('strval', (array) $title), 'strlen'));
            }
        }

        return $marks;
    }

    /**
     * A shortcut's words: its own for a named one, built for an offset ("En 3 días").
     *
     * @param  string  $preset
     * @return string
     */
    private function presetLabel(string $preset): string
    {
        if (preg_match(self::OFFSET, $preset, $offset)) {
            return trans_choice("wirepicker::picker.in.{$offset[2]}", (int) $offset[1], ['count' => $offset[1]], $this->locale);
        }

        return __("wirepicker::picker.presets.{$preset}", [], $this->locale);
    }

    /**
     * The `wire:model` the picker was given, split into the attribute (with its modifiers)
     * and the property, so each hidden input can bind its part of the value.
     *
     * @param  ComponentAttributeBag  $attributes
     * @return array{attribute: string, property: string}|null
     */
    public function model(ComponentAttributeBag $attributes): ?array
    {
        foreach ($attributes->getAttributes() as $key => $property) {
            if (str_starts_with($key, 'wire:model')) {
                return ['attribute' => $key, 'property' => (string) $property];
            }
        }

        return null;
    }

    /**
     * The texts the calendar shows, from the package's translations (`wirepicker::picker`),
     * which an app overrides by publishing them.
     *
     * @return array<string, mixed>
     */
    public function labels(): array
    {
        return [
            'placeholder' => $this->placeholder ?? __($this->range ? 'wirepicker::picker.placeholder-range' : 'wirepicker::picker.placeholder', [], $this->locale),
            'today' => __('wirepicker::picker.today', [], $this->locale),
            'clear' => __('wirepicker::picker.clear', [], $this->locale),
            'previous' => __('wirepicker::picker.previous', [], $this->locale),
            'next' => __('wirepicker::picker.next', [], $this->locale),
            'time' => __('wirepicker::picker.time', [], $this->locale),
            'start-time' => __('wirepicker::picker.start-time', [], $this->locale),
            'end-time' => __('wirepicker::picker.end-time', [], $this->locale),
            'done' => __('wirepicker::picker.done', [], $this->locale),
            'choose-month' => __('wirepicker::picker.choose-month', [], $this->locale),
            'choose-year' => __('wirepicker::picker.choose-year', [], $this->locale),
            'presets' => array_combine(
                $this->presets,
                array_map(fn (string $preset) => $this->presetLabel($preset), $this->presets),
            ) ?: (object) [],
        ];
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('wirepicker::components.wirepicker');
    }
}
