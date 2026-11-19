<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // `php artisan test --parallel`: every process has its own in-memory
        // database, so each one's first DownloadTask is id 1 — and the download
        // job works in storage/app/temp_downloads/{task id}/, a folder shared by
        // all processes. They then overwrite / delete each other's files and
        // the download tests fail at random. Give each process its own id range
        // (production has one database, so ids never collide there).
        $token = (int) ParallelTesting::token();
        if ($token > 0 && DB::getDriverName() === 'sqlite' && Schema::hasTable('download_tasks')) {
            DB::table('sqlite_sequence')->where('name', 'download_tasks')->delete();
            DB::table('sqlite_sequence')->insert(['name' => 'download_tasks', 'seq' => $token * 1000000]);
        }
    }
}
