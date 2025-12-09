<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use App\Helpers\ThaiBahtHelper;

class ThaiBahtHelperTest extends TestCase
{
    public function test_zero()
    {
        $this->assertEquals('ศูนย์บาทถ้วน', ThaiBahtHelper::toText(0));
    }

    public function test_simple_number()
    {
        $this->assertEquals('หนึ่งร้อยบาทถ้วน', ThaiBahtHelper::toText(100));
    }

    public function test_decimal()
    {
        $this->assertEquals('หนึ่งร้อยบาทห้าสิบสตางค์', ThaiBahtHelper::toText(100.50));
    }

    public function test_millions()
    {
        $this->assertEquals('หนึ่งล้านบาทถ้วน', ThaiBahtHelper::toText(1000000));
    }
}
