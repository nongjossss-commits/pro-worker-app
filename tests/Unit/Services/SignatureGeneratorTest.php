<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SignatureGeneratorService;

class SignatureGeneratorTest extends TestCase
{
    public function test_it_generates_consistent_signature_for_same_seed()
    {
        $service = new SignatureGeneratorService();
        $seed = 'EMP-123';

        $sig1 = $service->generate($seed);
        $sig2 = $service->generate($seed);

        // Assert binary data is identical
        $this->assertEquals($sig1, $sig2);
    }

    public function test_it_generates_different_signature_for_different_seed()
    {
        $service = new SignatureGeneratorService();

        $sig1 = $service->generate('EMP-123');
        $sig2 = $service->generate('EMP-456');

        // Assert binary data is different
        $this->assertNotEquals($sig1, $sig2);
    }

    public function test_it_returns_png_data()
    {
        $service = new SignatureGeneratorService();
        $sig = $service->generate('TEST');

        // Check PNG magic numbers
        $header = substr($sig, 0, 8);
        $expected = "\x89PNG\r\n\x1a\n";

        $this->assertEquals($expected, $header);
    }
}
