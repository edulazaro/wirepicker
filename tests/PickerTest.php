<?php

namespace EduLazaro\Wirepicker\Tests;

use EduLazaro\Wirepicker\WirepickerServiceProvider;
use Illuminate\Support\Facades\Blade;
use Orchestra\Testbench\TestCase;

class PickerTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [WirepickerServiceProvider::class];
    }

    /**
     * @param  string  $blade
     * @return string
     */
    private function render(string $blade): string
    {
        return Blade::render($blade);
    }

    public function test_a_single_date_binds_one_field_to_the_wire_model(): void
    {
        $html = $this->render('<x-wirepicker wire:model.live="startsOn" />');

        $this->assertStringContainsString('data-wp-mode="single"', $html);
        $this->assertSame(1, substr_count($html, 'data-wp-input='));
        $this->assertStringContainsString('wire:model.live="startsOn"', $html);
        $this->assertStringNotContainsString('data-wp-input="end"', $html);
    }

    public function test_a_range_binds_start_and_end_of_the_property(): void
    {
        $html = $this->render('<x-wirepicker range wire:model.live="period" />');

        $this->assertStringContainsString('data-wp-mode="range"', $html);
        $this->assertStringContainsString('wire:model.live="period.start"', $html);
        $this->assertStringContainsString('wire:model.live="period.end"', $html);
    }

    /**
     * Bound to Livewire the value is Livewire's: a value attribute would come back with every
     * morph and wipe what was picked.
     */
    public function test_a_bound_field_carries_no_value_attribute(): void
    {
        $html = $this->render('<x-wirepicker wire:model="startsOn" value="2026-10-08" />');

        $this->assertStringNotContainsString('value="2026-10-08"', $html);
    }

    /**
     * On a type="hidden" input the value is the attribute, so a morph empties it.
     */
    public function test_the_fields_are_hidden_text_inputs_not_hidden_inputs(): void
    {
        $html = $this->render('<x-wirepicker wire:model="startsOn" />');

        $this->assertStringContainsString('type="text" hidden', $html);
        $this->assertStringNotContainsString('type="hidden"', $html);
    }

    public function test_a_plain_form_posts_its_name_and_starts_from_its_value(): void
    {
        $single = $this->render('<x-wirepicker name="starts_on" value="2026-10-08" />');
        $range = $this->render('<x-wirepicker range name="period" :value="[\'start\' => \'2026-10-01\', \'end\' => \'2026-10-31\']" />');

        $this->assertStringContainsString('name="starts_on"', $single);
        $this->assertStringContainsString('value="2026-10-08"', $single);
        $this->assertStringContainsString('name="period[start]"', $range);
        $this->assertStringContainsString('name="period[end]"', $range);
        $this->assertStringContainsString('value="2026-10-31"', $range);
    }

    public function test_the_wire_model_stays_off_the_root_and_other_attributes_stay_on_it(): void
    {
        $html = $this->render('<x-wirepicker wire:model="startsOn" class="w-60" data-test="root" />');

        $this->assertMatchesRegularExpression('/<div class="wp-picker w-60"[^>]*data-test="root"/', $html);
        $this->assertSame(1, substr_count($html, 'wire:model="startsOn"'));
    }

    public function test_only_known_presets_are_offered_and_labels_follow_the_locale(): void
    {
        $this->app->setLocale('es');

        $html = html_entity_decode($this->render('<x-wirepicker range wire:model="period" :presets="[\'this-month\', \'next-month\', \'nonsense\']" />'));

        $this->assertStringContainsString('"this-month":"Este mes"', $html);
        $this->assertStringContainsString('"next-month":"Próximo mes"', $html);
        $this->assertStringNotContainsString('nonsense', $html);
        $this->assertStringContainsString('Cualquier fecha', $html);
        $this->assertStringContainsString('data-wp-locale="es"', $html);
    }

    public function test_english_is_the_fallback(): void
    {
        $this->app->setLocale('en');

        $html = $this->render('<x-wirepicker wire:model="startsOn" />');

        $this->assertStringContainsString('Pick a date', $html);
    }

    public function test_limits_and_the_label_target_reach_the_markup(): void
    {
        $html = $this->render('<x-wirepicker wire:model="startsOn" min="2026-01-01" max="2026-12-31" id="starts" :clearable="false" :week-start="0" />');

        $this->assertStringContainsString('data-wp-min="2026-01-01"', $html);
        $this->assertStringContainsString('data-wp-max="2026-12-31"', $html);
        $this->assertStringContainsString('data-wp-week-start="0"', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*data-wp-trigger[^>]*id="starts"/', $html);
        $this->assertStringNotContainsString('data-wp-clear', $html);
    }

    public function test_a_time_is_optional_and_carries_its_step(): void
    {
        $this->app->setLocale('es');

        $plain = $this->render('<x-wirepicker wire:model="startsOn" />');
        $timed = $this->render('<x-wirepicker range wire:model="period" time :step="30" default-time="10:00" />');

        $this->assertStringNotContainsString('data-wp-time', $plain);
        $this->assertStringContainsString('data-wp-time', $timed);
        $this->assertStringContainsString('data-wp-step="30"', $timed);
        $this->assertStringContainsString('data-wp-default-time="10:00"', $timed);
        $this->assertStringContainsString('Listo', $timed);
        $this->assertStringContainsString('Inicio', $timed);
    }

    public function test_each_mode_keeps_its_own_shortcuts(): void
    {
        $this->app->setLocale('es');

        $single = $this->render(<<<'BLADE'
            <x-wirepicker wire:model="dueOn" :presets="['today', 'tomorrow', '+3d', '+1m', 'this-month', '+0d', 'nonsense']" />
            BLADE);
        $range = $this->render(<<<'BLADE'
            <x-wirepicker range wire:model="period" :presets="['this-month', 'tomorrow', '+3d']" />
            BLADE);

        $this->assertStringContainsString('Mañana', $single);
        $this->assertStringContainsString('Dentro de 3 días', $single);
        $this->assertStringContainsString('Dentro de 1 mes', $single);
        $this->assertStringNotContainsString('Este mes', $single);
        $this->assertStringNotContainsString('+0d', $single);
        $this->assertStringContainsString('Este mes', $range);
        $this->assertStringNotContainsString('Mañana', $range);
    }

    public function test_marked_days_carry_their_titles(): void
    {
        $html = $this->render(<<<'BLADE'
            <x-wirepicker wire:model="startsOn" :marked="['2026-10-08' => 'Visita', '2026-10-14' => ['Llamada', 'Entrega'], 'nonsense' => 'x']" />
            BLADE);
        $bare = $this->render(<<<'BLADE'
            <x-wirepicker wire:model="startsOn" :marked="['2026-10-08', '2026-10-09']" />
            BLADE);

        $this->assertStringContainsString('data-wp-marked="{&quot;2026-10-08&quot;:&quot;Visita&quot;,&quot;2026-10-14&quot;:&quot;Llamada · Entrega&quot;}"', $html);
        $this->assertStringContainsString('data-wp-marked="{&quot;2026-10-08&quot;:&quot;&quot;,&quot;2026-10-09&quot;:&quot;&quot;}"', $bare);
        $this->assertStringNotContainsString('data-wp-marked', $this->render('<x-wirepicker wire:model="startsOn" />'));
    }
}
