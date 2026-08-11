<?php

namespace App\Actions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DemoAlreadyUploadedException extends RuntimeException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('This demo has already been uploaded.');
    }

    public function render(Request $request): JsonResponse|false
    {
        if (! $request->is('api/*')) {
            return false;
        }

        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'demo_already_uploaded',
        ], 409);
    }
}
