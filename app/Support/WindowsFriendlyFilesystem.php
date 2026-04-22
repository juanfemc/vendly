<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class WindowsFriendlyFilesystem extends Filesystem
{
    public function replace($path, $content, $mode = null)
    {
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;
        $directory = dirname($path);

        if (! $this->exists($directory)) {
            $this->makeDirectory($directory, 0777, true, true);
        }

        $tempPath = tempnam($directory, basename($path));

        if ($tempPath === false) {
            throw new RuntimeException("Unable to create a temporary file for [{$path}].");
        }

        file_put_contents($tempPath, $content);

        if (! is_null($mode)) {
            @chmod($tempPath, $mode);
        }

        if (DIRECTORY_SEPARATOR === '\\' && $this->exists($path)) {
            @unlink($path);
        }

        if (@rename($tempPath, $path)) {
            return;
        }

        file_put_contents($path, $content, LOCK_EX);
        @unlink($tempPath);
    }
}
