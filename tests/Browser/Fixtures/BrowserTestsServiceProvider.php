<?php

namespace Tests\Browser\Fixtures;

use Carbon\Laravel\ServiceProvider;

class BrowserTestsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'tests');
        $this->loadRoutesFrom(__DIR__. '/routes/web.php');
    }
}
