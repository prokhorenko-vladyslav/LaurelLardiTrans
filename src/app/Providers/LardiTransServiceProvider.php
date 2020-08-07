<?php

namespace Laurel\LardiTrans\App\Providers;

use Illuminate\Support\ServiceProvider;
use Laurel\LardiTrans\App\Console\Commands\LardiFetchCountries;

class LardiTransServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            LardiFetchCountries::class
        ]);
        $this->mergeConfigFrom(__DIR__ . '/../../config/lardi_trans.php', 'laurel.lardi_trans');
        $this->publishes([
            __DIR__ . '/../../config/lardi_trans.php' => config_path('laurel/lardi_trans.php')
        ], 'config');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
