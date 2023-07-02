<?php

use Illuminate\Filesystem\Filesystem;

use function Pest\Laravel\postJson;
use function Pineblade\Pineblade\Helpers\s3i_path;

test('when the script does not exists, the endpoint must return null', function () {
    postJson(route('pineblade.s3i'), [
        'action' => 'foo',
        'params' => [123],
    ])
        ->assertOk()
        ->assertJsonPath('payload', null);
});

test('when the script exists, the endpoint must execute it, and return its return value', function () {
    $filesystem = new Filesystem();

    if (!$filesystem->isDirectory(s3i_path())) {
        $filesystem->makeDirectory(s3i_path(), recursive: true);
    }

    $scriptName = '__testing';

    $scriptPath = s3i_path($scriptName).'.php';

    $filesystem->put($scriptPath, '<?php return fn () => 123;');

    postJson(route('pineblade.s3i'), [
        'action' => $scriptName,
        'params' => [123],
    ])
        ->assertOk()
        ->assertJsonPath('payload', 123);
});
