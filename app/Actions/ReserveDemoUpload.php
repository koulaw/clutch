<?php

namespace App\Actions;

use App\AnalysisStatus;
use App\Models\Demo;
use App\Models\User;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReserveDemoUpload
{
    public function __construct(
        private FilesystemManager $filesystems,
        private Repository $config,
    ) {}

    /**
     * @return array{demo: Demo, upload: array{url: string, headers: array<string, mixed>}, expires_at: Carbon}
     */
    public function handle(User $user, int $sizeBytes, string $checksumSha256): array
    {
        $disk = (string) $this->config->get('demo_upload.disk');
        $publicId = (string) Str::ulid();
        $storagePath = "demos/{$user->getKey()}/{$publicId}.dem";
        $expiresAt = now()->addMinutes((int) $this->config->get('demo_upload.url_ttl_minutes'));

        $upload = $this->filesystems->disk($disk)->temporaryUploadUrl(
            $storagePath,
            $expiresAt,
            [
                'ContentType' => 'application/octet-stream',
                'ContentDisposition' => 'attachment',
                'ContentLength' => $sizeBytes,
                'ChecksumSHA256' => base64_encode(hex2bin($checksumSha256)),
            ],
        );

        $demo = $user->demos()->create([
            'public_id' => $publicId,
            'storage_disk' => $disk,
            'storage_path' => $storagePath,
            'checksum_sha256' => $checksumSha256,
            'size_bytes' => $sizeBytes,
            'status' => AnalysisStatus::Uploaded,
            'uploaded_at' => null,
        ]);

        return [
            'demo' => $demo,
            'upload' => $upload,
            'expires_at' => $expiresAt,
        ];
    }
}
