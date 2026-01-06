<?php

namespace Tests\Feature\Pdf;

use App\Services\SignatureGeneratorService;
use Tests\TestCase;

class SignatureTest extends TestCase
{
    public function test_signature_generation_returns_image_data()
    {
        $service = new SignatureGeneratorService();
        $seed = 'test-seed-123';

        $imageContent = $service->generate($seed);

        $this->assertNotEmpty($imageContent);

        // Verify it is a PNG
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->assertEquals('image/png', $finfo->buffer($imageContent));
    }

    public function test_signature_consistency()
    {
        $service = new SignatureGeneratorService();
        $seed = 'consistent-seed-456';

        $sig1 = $service->generate($seed);
        $sig2 = $service->generate($seed);

        $this->assertEquals($sig1, $sig2, "Same seed should produce identical signature");
    }

    public function test_signature_diversity()
    {
        $service = new SignatureGeneratorService();

        $sig1 = $service->generate('user-1');
        $sig2 = $service->generate('user-2');

        $this->assertNotEquals($sig1, $sig2, "Different seeds should produce different signatures");
    }
}
