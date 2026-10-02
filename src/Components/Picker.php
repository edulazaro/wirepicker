<?php

namespace EduLazaro\Wirepicker\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

/**
 * A date, or a date range, picked from a calendar.
 *
 * The value lives in hidden inputs, so the picker binds like any input: `wire:model` (a date
 * as `Y-m-d`; a range as an array with `start` and `end`), a plain form field (`name`), or
 * Alpine through the `wirepicker:change` event. The calendar itself is drawn by
 * wirepicker.js, which keeps the package free of any CSS or JS framework.
 */
class Picker extends Component
{
    public const PRESETS = ['today', 'this-week', 'next-7-days', 'this-month', 'next-month', 'last-month', 'this-year'];

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
    ) {
        $this->locale = str_replace('_', '-', $locale ?? app()->getLocale());
        $this->presets = array_values(array_intersect($presets, self::PRESETS));
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
            'presets' => array_combine(
                $this->presets,
                array_map(fn (string $preset) => __("wirepicker::picker.presets.{$preset}", [], $this->locale), $this->presets),
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
