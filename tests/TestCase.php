<?php

namespace Tests;

use Illuminate\Foundation\Testing\CreatesApplication;
use Illuminate\Foundation\Testing\RefreshDatabase; // เปลี่ยนจาก LazilyRefreshDatabase เป็น RefreshDatabase
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase; // ใช้ RefreshDatabase แทน
}