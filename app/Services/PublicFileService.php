<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PublicFileService
{
    private const ALLOWED_DIRECTORIES = [
        'banners/',
        'categories/',
        'products/',
        'stores/',
    ];

    public function delete(?string $path): void
    {
        $path = $this->safePath($path);

        if (! $path) {
            return;
        }

        $disk = Storage::disk('public');
        $disk->delete($path);

        if ($disk->exists($path)) {
            @unlink($disk->path($path));
        }
    }

    public function deleteMany(iterable $paths): void
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
    }

    private function safePath(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));

        if ($path === ''
            || str_starts_with($path, '/')
            || preg_match('/^[a-zA-Z]:\//', $path)
            || str_contains($path, '../')
            || str_contains($path, '/..')
            || $path === '..'
        ) {
            return null;
        }

        foreach (self::ALLOWED_DIRECTORIES as $directory) {
            if (str_starts_with($path, $directory)) {
                return $path;
            }
        }

        return null;
    }
}
