<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PublicFileService
{
    public function delete(?string $path): void
    {
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
}
