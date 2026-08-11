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
    public function handle(User $user, string $filename, int $sizeBytes, string $checksumSha256): array
    {
        $disk = (string) $this->config->get('demo_upload.disk');
        $publicId = (string) Str::ulid();
        $extension = Str::endsWith(Str::lower($filename), '.zst') ? '.dem.zst' : '.dem';
        $storagePath = "demos/{$user->getKey()}/{$publicId}{$extension}";
        $expiresAt = now()->addMinutes((int) $this->config->get('demo_upload.url_ttl_minutes'));

        $demo = $user->demos()->firstOrCreate(
            ['checksum_sha256' => $checksumSha256],
            [
                'public_id' => $publicId,
                'storage_disk' => $disk,
                'storage_path' => $storagePath,
                'size_bytes' => $sizeBytes,
                'status' => AnalysisStatus::Uploaded,
                'uploaded_at' => null,
            ],
        );

        if ($demo->uploaded_at !== null) {
            throw new DemoAlreadyUploadedException;
        }

        $expectedStoragePath = "demos/{$user->getKey()}/{$demo->public_id}{$extension}";

        if ($demo->storage_path !== $expectedStoragePath) {
            $this->filesystems->disk($demo->storage_disk)->delete($demo->storage_path);
        }

        if ($demo->storage_path !== $expectedStoragePath || $demo->size_bytes !== $sizeBytes) {
            $demo->update([
                'storage_disk' => $disk,
                'storage_path' => $expectedStoragePath,
                'size_bytes' => $sizeBytes,
            ]);
        }

        $upload = $this->filesystems->disk($disk)->temporaryUploadUrl(
            $demo->storage_path,
            $expiresAt,
            [
                'ContentType' => 'application/octet-stream',
                'ContentDisposition' => 'attachment',
                'ContentLength' => $demo->size_bytes,
                'ChecksumSHA256' => base64_encode(hex2bin($demo->checksum_sha256)),
            ],
        );

        return [
            'demo' => $demo,
            'upload' => $upload,
            'expires_at' => $expiresAt,
        ];
    }
}
