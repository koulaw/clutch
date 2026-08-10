<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

it('defines the persistent production services', function () {
    expect(config('database.connections.pgsql.driver'))->toBe('pgsql')
        ->and(config('cache.stores.redis.driver'))->toBe('redis')
        ->and(config('queue.connections.redis.driver'))->toBe('redis')
        ->and(config('queue.connections.redis.block_for'))->toBeInt()
        ->and(config('queue.connections.redis.after_commit'))->toBeTrue()
        ->and(config('filesystems.disks.s3.driver'))->toBe('s3');
});

it('can create an S3 compatible filesystem adapter', function () {
    $disk = Storage::build([
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'clutch-test',
        'endpoint' => 'http://127.0.0.1:9002',
        'use_path_style_endpoint' => true,
        'throw' => true,
    ]);

    expect($disk)->toBeInstanceOf(FilesystemAdapter::class);
});
