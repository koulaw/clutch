<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmDemoUpload;
use App\Actions\ReserveDemoUpload;
use App\Http\Requests\ConfirmDemoUploadRequest;
use App\Http\Requests\CreateDemoUploadRequest;
use App\Models\Demo;
use Illuminate\Http\JsonResponse;

class DemoUploadController extends Controller
{
    public function store(CreateDemoUploadRequest $request, ReserveDemoUpload $uploads): JsonResponse
    {
        $reservation = $uploads->handle(
            $request->user(),
            $request->string('filename')->toString(),
            $request->integer('size_bytes'),
            $request->string('checksum_sha256')->toString(),
        );

        return response()->json([
            'data' => [
                'demo_id' => $reservation['demo']->public_id,
                'upload_url' => $reservation['upload']['url'],
                'upload_headers' => $reservation['upload']['headers'],
                'expires_at' => $reservation['expires_at']->toIso8601String(),
            ],
        ], 201);
    }

    public function confirm(
        ConfirmDemoUploadRequest $request,
        Demo $demo,
        ConfirmDemoUpload $uploads,
    ): JsonResponse {
        $confirmedDemo = $uploads->handle($demo);

        return response()->json([
            'data' => [
                'demo_id' => $confirmedDemo->public_id,
                'status' => $confirmedDemo->status->value,
                'uploaded_at' => $confirmedDemo->uploaded_at?->toIso8601String(),
            ],
        ]);
    }
}
