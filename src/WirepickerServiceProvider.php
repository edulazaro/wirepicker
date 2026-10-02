<?php

namespace EduLazaro\Wirepicker;

use EduLazaro\Wirepicker\Components\Picker;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class WirepickerServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wirepicker');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'wirepicker');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/wirepicker'),
        ], 'wirepicker-lang');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/wirepicker'),
        ], 'wirepicker-views');

        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/wirepicker/css'),
            __DIR__.'/../resources/js' => public_path('vendor/wirepicker/js'),
        ], 'wirepicker-assets');

        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $blade->component(Picker::class, 'wirepicker');
        });
    }
}
