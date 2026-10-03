<?php

namespace EduLazaro\Wirepicker\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

/**
 * A time of day, with no date: "at what time each morning", "opens at". Picked from a list of
 * times every `step` minutes, so the browser's own time field (AM/PM on an English browser,
 * unstyleable) is never shown.
 *
 * `<x-wiretimepicker>`: its own component and not a mode of `<x-wirepicker>`: a picker is about real dates (ranges,
 * weekends, unavailable days), and none of that means anything for a time. It shares the
 * picker's popover, themes and wiring. The value is `H:i`, `''` when empty.
 */
class TimePicker extends Component
{
    public string $locale;

    /**
     * @param  string|null  $placeholder  Shown while empty.
     * @param  string|null  $min  The first time offered (H:i).
     * @param  string|null  $max  The last time offered (H:i).
     * @param  int  $step  Minutes between the times offered.
     * @param  bool  $clearable  A button to empty it.
     * @param  string|null  $name  Field name for a plain form.
     * @param  string|null  $value  Initial value outside Livewire (H:i).
     * @param  string  $size  `sm` or `md`.
     * @param  string|null  $locale  How the time is written (09:00 or 9:00 AM); the app's by default.
     * @param  string|null  $id  For a <label for="…">: set on the button that opens it.
     */
    public function __construct(
        public ?string $placeholder = null,
        public ?string $min = null,
        public ?string $max = null,
        public int $step = 15,
        public bool $clearable = true,
        public ?string $name = null,
        public ?string $value = null,
        public string $size = 'md',
        ?string $locale = null,
        public ?string $id = null,
    ) {
        $this->locale = str_replace('_', '-', $locale ?? app()->getLocale());
        $this->min = self::time($min);
        $this->max = self::time($max);
    }

    /**
     * An H:i time, or null for anything else.
     *
     * @param  string|null  $time
     * @return string|null
     */
    private static function time(?string $time): ?string
    {
        return $time !== null && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) ? $time : null;
    }

    /**
     * The `wire:model` the field was given.
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
     * @return array<string, string>
     */
    public function labels(): array
    {
        return [
            'placeholder' => $this->placeholder ?? __('wirepicker::picker.placeholder-time', [], $this->locale),
            'clear' => __('wirepicker::picker.clear', [], $this->locale),
        ];
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('wirepicker::components.wiretimepicker');
    }
}
