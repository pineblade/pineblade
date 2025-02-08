<?php

namespace Tests;

use Illuminate\Config\Repository;

trait DefinesEnvironment
{
    protected function defineEnvironment($app)
    {
        tap($app['config'], function (Repository $config) {
            $config->set('app.key', 'base64:9ULi3nSsn1M+JWDGI+v7g1uT5ldvMp4ZCD4JATiffWk=');
            $config->set('pineblade.experimental_features.components.enabled', true);
            $config->set('pineblade.experimental_features.minification.enabled', true);
            $config->set('pineblade.experimental_features.server_side_script_injection.enabled', true);
        });
    }
}
