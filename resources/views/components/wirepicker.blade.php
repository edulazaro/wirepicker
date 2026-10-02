{{--
    Hidden fields hold the value (Y-m-d, or Y-m-d H:i with `time`): wire:model, a plain form or
    Alpine all read them.
    wirepicker.js draws the calendar and writes them back, firing `input` and `change`.
--}}
<div {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'wp-picker']) }}
     data-wirepicker
     data-wp-mode="{{ $range ? 'range' : 'single' }}"
     data-wp-size="{{ $size }}"
     data-wp-locale="{{ $locale }}"
     @if ($weekStart !== null) data-wp-week-start="{{ $weekStart }}" @endif
     @if ($min) data-wp-min="{{ $min }}" @endif
     @if ($max) data-wp-max="{{ $max }}" @endif
     @if ($clearable) data-wp-clearable @endif
     @if ($marked) data-wp-marked="{{ json_encode($marked, JSON_UNESCAPED_UNICODE) }}" @endif
     @if ($time) data-wp-time data-wp-step="{{ $step }}" @if ($defaultTime) data-wp-default-time="{{ $defaultTime }}" @endif @endif
     data-wp-labels="{{ json_encode($labels(), JSON_UNESCAPED_UNICODE) }}">
    @foreach ($range ? ['start', 'end'] : ['start'] as $part)
        {{-- type="text" hidden, not type="hidden": on a hidden input the value IS the attribute,
             so a Livewire morph (whose HTML has no value) empties it after every request. --}}
        <input type="text" hidden tabindex="-1" aria-hidden="true" autocomplete="off" data-wp-input="{{ $part }}"
            @if ($bound = $model($attributes)) {{ $bound['attribute'] }}="{{ $bound['property'].($range ? '.'.$part : '') }}" @endif
            @if ($name) name="{{ $range ? $name.'['.$part.']' : $name }}" @endif
            {{-- Bound to Livewire, the value is Livewire's: a value attribute here would be put
                 back by every morph and wipe what was picked. --}}
            @unless ($model($attributes)) value="{{ is_array($value) ? ($value[$part] ?? '') : ($part === 'start' ? $value : '') }}" @endunless>
    @endforeach

    <button type="button" class="wp-trigger" data-wp-trigger aria-haspopup="dialog" aria-expanded="false" @if ($id) id="{{ $id }}" @endif>
        <svg class="wp-trigger-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
        {{-- wire:ignore: wirepicker.js paints this, and a morph must not paint it back. --}}
        <span class="wp-trigger-text" data-wp-display wire:ignore>{{ $labels()['placeholder'] }}</span>
    </button>

    @if ($clearable)
        <button type="button" class="wp-clear" data-wp-clear aria-label="{{ $labels()['clear'] }}" hidden wire:ignore>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
        </button>
    @endif
</div>
