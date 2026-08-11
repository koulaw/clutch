<?php

use App\Actions\ManageUserQuota;
use App\Models\Demo;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    Queue::fake();
    config()->set('demo_upload.disk', 's3');
    config()->set('demo_upload.rate_limit_per_minute', 100);
    Storage::fake('s3');
});

it('reserves a private generated path and signs a direct upload', function () {
    $signedOptions = [];
    Storage::disk('s3')->buildTemporaryUploadUrlsUsing(
        function (string $path, DateTimeInterface $expiration, array $options) use (&$signedOptions): array {
            $signedOptions = $options;

            return [
                'url' => 'https://storage.example.test/'.$path,
                'headers' => $options,
            ];
        },
    );

    $user = User::factory()->create();
    $checksum = str_repeat('ab', 32);

    $response = $this->actingAs($user)->postJson(route('api.demos.upload.store'), [
        'filename' => 'My Match.DEM',
        'size_bytes' => 1_024,
        'checksum_sha256' => strtoupper($checksum),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.upload_headers.ContentLength', 1_024)
        ->assertJsonPath('data.upload_headers.ChecksumSHA256', base64_encode(hex2bin($checksum)));

    $demo = Demo::query()->where('public_id', $response->json('data.demo_id'))->sole();

    expect($demo->user->is($user))->toBeTrue()
        ->and($demo->storage_path)->toStartWith("demos/{$user->id}/")
        ->and($demo->storage_path)->toEndWith('.dem')
        ->and($demo->storage_path)->not->toContain('My Match')
        ->and($demo->checksum_sha256)->toBe($checksum)
        ->and($demo->uploaded_at)->toBeNull()
        ->and($signedOptions)->toMatchArray([
            'ContentType' => 'application/octet-stream',
            'ContentDisposition' => 'attachment',
            'ContentLength' => 1_024,
            'ChecksumSHA256' => base64_encode(hex2bin($checksum)),
        ]);
});

it('reserves a generated zstandard path without retaining the client filename', function () {
    Storage::disk('s3')->buildTemporaryUploadUrlsUsing(
        fn (string $path): array => ['url' => 'https://storage.example.test/'.$path, 'headers' => []],
    );

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('api.demos.upload.store'), [
        'filename' => 'FACEIT Secret Match.dem.zst',
        'size_bytes' => 1_024,
        'checksum_sha256' => str_repeat('a', 64),
    ])->assertCreated();

    $demo = Demo::query()->where('public_id', $response->json('data.demo_id'))->sole();

    expect($demo->storage_path)->toStartWith("demos/{$user->id}/")
        ->toEndWith('.dem.zst')
        ->not->toContain('FACEIT');
});

it('renews the signed URL for an unfinished duplicate reservation', function () {
    Storage::disk('s3')->buildTemporaryUploadUrlsUsing(
        fn (string $path): array => ['url' => 'https://storage.example.test/'.$path, 'headers' => []],
    );

    $user = User::factory()->create();
    $checksum = str_repeat('b', 64);
    $demo = Demo::factory()->for($user)->create([
        'checksum_sha256' => $checksum,
        'size_bytes' => 2_048,
        'uploaded_at' => null,
    ]);
    $previousStoragePath = $demo->storage_path;
    Storage::disk('s3')->put($previousStoragePath, 'incomplete-upload');

    $response = $this->actingAs($user)->postJson(route('api.demos.upload.store'), [
        'filename' => 'retry.dem.zst',
        'size_bytes' => 2_048,
        'checksum_sha256' => $checksum,
    ])->assertCreated();

    $demo->refresh();

    $response->assertJsonPath('data.demo_id', $demo->public_id)
        ->assertJsonPath('data.upload_url', 'https://storage.example.test/'.$demo->storage_path);

    expect($user->demos()->count())->toBe(1)
        ->and($demo->storage_path)->toEndWith('.dem.zst');
    Storage::disk('s3')->assertMissing($previousStoragePath);
});

it('returns an explicit conflict for an already uploaded duplicate', function () {
    Storage::disk('s3')->buildTemporaryUploadUrlsUsing(
        fn (string $path): array => ['url' => 'https://storage.example.test/'.$path, 'headers' => []],
    );

    $user = User::factory()->create();
    $checksum = str_repeat('c', 64);
    Demo::factory()->for($user)->create([
        'checksum_sha256' => $checksum,
        'uploaded_at' => now(),
    ]);

    $this->actingAs($user)->postJson(route('api.demos.upload.store'), [
        'filename' => 'duplicate.dem',
        'size_bytes' => 1_024,
        'checksum_sha256' => $checksum,
    ])->assertConflict()
        ->assertJsonPath('code', 'demo_already_uploaded');

    expect($user->demos()->count())->toBe(1);
});

it('validates upload metadata before creating a reservation', function (array $payload, string $error) {
    $this->actingAs(User::factory()->create())
        ->postJson(route('api.demos.upload.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($error);

    expect(Demo::query()->count())->toBe(0);
})->with([
    'extension' => [[
        'filename' => 'match.zip',
        'size_bytes' => 1_024,
        'checksum_sha256' => str_repeat('a', 64),
    ], 'filename'],
    'empty file' => [[
        'filename' => 'match.dem',
        'size_bytes' => 0,
        'checksum_sha256' => str_repeat('a', 64),
    ], 'size_bytes'],
    'over 500 MB' => [[
        'filename' => 'match.dem',
        'size_bytes' => 500 * 1024 * 1024 + 1,
        'checksum_sha256' => str_repeat('a', 64),
    ], 'size_bytes'],
    'checksum' => [[
        'filename' => 'match.dem',
        'size_bytes' => 1_024,
        'checksum_sha256' => 'not-a-sha256',
    ], 'checksum_sha256'],
]);

it('requires an authenticated verified user', function () {
    $payload = [
        'filename' => 'match.dem',
        'size_bytes' => 1_024,
        'checksum_sha256' => str_repeat('a', 64),
    ];

    $this->postJson(route('api.demos.upload.store'), $payload)->assertUnauthorized();

    $this->actingAs(User::factory()->unverified()->create())
        ->postJson(route('api.demos.upload.store'), $payload)
        ->assertForbidden();
});

it('confirms a valid CS2 demo and consumes one daily import', function () {
    $user = User::factory()->create();
    $content = "PBDEMS2\0demo-payload";
    $demo = Demo::factory()->for($user)->create([
        'size_bytes' => strlen($content),
        'checksum_sha256' => hash('sha256', $content),
        'uploaded_at' => null,
    ]);
    Storage::disk('s3')->put($demo->storage_path, $content);

    $this->actingAs($user)
        ->postJson(route('api.demos.upload.confirm', $demo))
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'queued');

    expect($demo->fresh()->uploaded_at)->not->toBeNull()
        ->and(app(ManageUserQuota::class)->usage($user)['imports']['used'])->toBe(1);
});

it('confirms a zstandard-compressed demo', function () {
    $user = User::factory()->create();
    $content = "\x28\xB5\x2F\xFDcompressed-demo";
    $demo = Demo::factory()->for($user)->create([
        'storage_path' => 'demos/'.Str::ulid().'.dem.zst',
        'size_bytes' => strlen($content),
        'checksum_sha256' => hash('sha256', $content),
        'uploaded_at' => null,
    ]);
    Storage::disk('s3')->put($demo->storage_path, $content);

    $this->actingAs($user)
        ->postJson(route('api.demos.upload.confirm', $demo))
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'queued');

    expect($demo->fresh()->uploaded_at)->not->toBeNull();
});

it('rejects a zstandard upload without the frame signature', function () {
    $user = User::factory()->create();
    $content = 'not-zstandard';
    $demo = Demo::factory()->for($user)->create([
        'storage_path' => 'demos/'.Str::ulid().'.dem.zst',
        'size_bytes' => strlen($content),
        'uploaded_at' => null,
    ]);
    Storage::disk('s3')->put($demo->storage_path, $content);

    $this->actingAs($user)
        ->postJson(route('api.demos.upload.confirm', $demo))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('demo');

    Storage::disk('s3')->assertMissing($demo->storage_path);
});

it('makes confirmation idempotent', function () {
    $user = User::factory()->create();
    $content = "PBDEMS2\0demo-payload";
    $demo = Demo::factory()->for($user)->create([
        'size_bytes' => strlen($content),
        'checksum_sha256' => hash('sha256', $content),
        'uploaded_at' => null,
    ]);
    Storage::disk('s3')->put($demo->storage_path, $content);

    $this->actingAs($user)->postJson(route('api.demos.upload.confirm', $demo))->assertSuccessful();
    $this->actingAs($user)->postJson(route('api.demos.upload.confirm', $demo))->assertSuccessful();

    expect(app(ManageUserQuota::class)->usage($user)['imports']['used'])->toBe(1);
});

it('rejects confirmation by another user', function () {
    $demo = Demo::factory()->create(['uploaded_at' => null]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('api.demos.upload.confirm', $demo))
        ->assertForbidden();
});

it('rejects and removes uploads with invalid stored content', function (string $content, ?int $reservedSize) {
    $user = User::factory()->create();
    $demo = Demo::factory()->for($user)->create([
        'size_bytes' => $reservedSize ?? strlen($content),
        'uploaded_at' => null,
    ]);
    Storage::disk('s3')->put($demo->storage_path, $content);

    $this->actingAs($user)
        ->postJson(route('api.demos.upload.confirm', $demo))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('demo');

    Storage::disk('s3')->assertMissing($demo->storage_path);
    expect(app(ManageUserQuota::class)->usage($user)['imports']['used'])->toBe(0);
})->with([
    'wrong header' => ['NOTADEMO-payload', null],
    'wrong size' => ["PBDEMS2\0demo-payload", 999],
]);

it('returns an explicit error when the daily quota is exhausted', function () {
    config()->set('quotas.daily_imports', 1);
    $user = User::factory()->create();
    app(ManageUserQuota::class)->consumeImport($user);
    $content = "PBDEMS2\0demo-payload";
    $demo = Demo::factory()->for($user)->create([
        'size_bytes' => strlen($content),
        'uploaded_at' => null,
    ]);
    Storage::disk('s3')->put($demo->storage_path, $content);

    $this->actingAs($user)
        ->postJson(route('api.demos.upload.confirm', $demo))
        ->assertTooManyRequests()
        ->assertJsonPath('code', 'quota_exceeded')
        ->assertJsonPath('quota', 'daily_imports');

    expect($demo->fresh()->uploaded_at)->toBeNull();
});

it('rate limits upload requests per user', function () {
    config()->set('demo_upload.rate_limit_per_minute', 1);
    $user = User::factory()->create();
    $payload = [
        'filename' => 'invalid.zip',
        'size_bytes' => 1_024,
        'checksum_sha256' => str_repeat('a', 64),
    ];

    $this->actingAs($user)->postJson(route('api.demos.upload.store'), $payload)->assertUnprocessable();
    $this->actingAs($user)
        ->postJson(route('api.demos.upload.store'), $payload)
        ->assertTooManyRequests()
        ->assertJsonPath('code', 'upload_rate_limit_exceeded');
});
