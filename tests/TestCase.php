<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // We are removing the custom getPackageProviders method
    // to allow Laravel's default auto-discovery to handle all service providers,
    // including the Spatie PermissionServiceProvider, correctly.
}