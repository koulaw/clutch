<?php

namespace App\Actions;

use App\Models\Demo;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ValidateDemoUpload
{
    private const Header = "PBDEMS2\0";

    private const ZstandardHeader = "\x28\xB5\x2F\xFD";

    public function __construct(private FilesystemManager $filesystems) {}

    public function handle(Demo $demo): void
    {
        $disk = $this->filesystems->disk($demo->storage_disk);

        if (! $disk->exists($demo->storage_path)) {
            throw ValidationException::withMessages([
                'demo' => 'The uploaded demo could not be found.',
            ]);
        }

        if ($disk->size($demo->storage_path) !== $demo->size_bytes) {
            $disk->delete($demo->storage_path);

            throw ValidationException::withMessages([
                'demo' => 'The uploaded demo size does not match the reserved size.',
            ]);
        }

        $stream = $disk->readStream($demo->storage_path);

        try {
            $header = fread($stream, 8);
        } finally {
            fclose($stream);
        }

        $isSupported = Str::endsWith($demo->storage_path, '.zst')
            ? str_starts_with($header, self::ZstandardHeader)
            : $header === self::Header;

        if (! $isSupported) {
            $disk->delete($demo->storage_path);

            throw ValidationException::withMessages([
                'demo' => 'The uploaded file is not a supported Counter-Strike 2 demo.',
            ]);
        }
    }
}
