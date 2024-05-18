<?php

namespace Pineblade\Pineblade\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use function Pineblade\Pineblade\Helpers\s3i_path;

class S3IController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string'],
            'params' => ['array', 'nullable'],
        ]);
        $actionFile = "{$request->json('action')}.php";
        $script = s3i_path(basename($actionFile));
        if (file_exists($script)) {
            $callable = require_once $script;
            return response()->json([
                'payload' => $callable(...$request->json('params', [])),
            ]);
        }
        return response()->json([
            'payload' => null,
        ]);
    }
}
