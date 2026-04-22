<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $compiledPath = dirname(__DIR__).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'testing'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.bin2hex(random_bytes(10));

        if (! is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        putenv("VIEW_COMPILED_PATH={$compiledPath}");
        $_ENV['VIEW_COMPILED_PATH'] = $compiledPath;
        $_SERVER['VIEW_COMPILED_PATH'] = $compiledPath;

        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
