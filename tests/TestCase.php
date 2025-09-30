<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionServiceProvider; // เพิ่มบรรทัดนี้

abstract class TestCase extends BaseTestCase
{
    /**
     * The service providers that should be loaded for test purposes.
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ...parent::getPackageProviders($app),
            PermissionServiceProvider::class, // เพิ่มบรรทัดนี้
        ];
    }
}