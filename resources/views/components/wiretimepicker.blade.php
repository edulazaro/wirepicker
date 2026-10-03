{{--
    A time of day (H:i) in a hidden field, like the picker's dates: wire:model, a plain form or
    Alpine read it. wirepicker.js draws the list of times in the shared popover.
--}}
<div {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'wp-picker']) }}
     data-wirepicker
     data-wp-mode="time"
     data-wp-size="{{ $size }}"
     data-wp-locale="{{ $locale }}"
     data-wp-step="{{ $step }}"
     @if ($min) data-wp-min-time="{{ $min }}" @endif
     @if ($max) data-wp-max-time="{{ $max }}" @endif
     @if ($clearable) data-wp-clearable @endif
     data-wp-labels="{{ json_encode($labels(), JSON_UNESCAPED_UNICODE) }}">
    {{-- type="text" hidden, not type="hidden": a Livewire morph empties a hidden input. --}}
    <input type="text" hidden tabindex="-1" aria-hidden="true" autocomplete="off" data-wp-input="start"
        @if ($bound = $model($attributes)) {{ $bound['attribute'] }}="{{ $bound['property'] }}" @endif
        @if ($name) name="{{ $name }}" @endif
        @unless ($model($attributes)) value="{{ $value }}" @endunless>

    <button type="button" class="wp-trigger" data-wp-trigger aria-haspopup="dialog" aria-expanded="false" @if ($id) id="{{ $id }}" @endif>
        <svg class="wp-trigger-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
        <span class="wp-trigger-text" data-wp-display wire:ignore>{{ $labels()['placeholder'] }}</span>
    </button>

    @if ($clearable)
        <button type="button" class="wp-clear" data-wp-clear aria-label="{{ $labels()['clear'] }}" hidden wire:ignore>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
        </button>
    @endif
</div>
