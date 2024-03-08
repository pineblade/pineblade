<?php

namespace Tests;

trait DefinesEnvironment
{
    protected function defineEnvironment($app)
    {
        $app->make('config')->set('app.key', 'base64:9ULi3nSsn1M+JWDGI+v7g1uT5ldvMp4ZCD4JATiffWk=');
    }
}
