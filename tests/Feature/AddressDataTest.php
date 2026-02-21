<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AddressDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the data file exists by running the command
        // We can mock the Http facade to avoid external calls during test if preferred,
        // but for now let's try to run it.
        // If external calls are blocked in test environment, we might need to skip or mock.
        // Given I could run it in bash session, it should work here too.

        // Check if file exists, if not generate it.
        $path = storage_path('app/public/data/thai-address-data.json');
        if (!File::exists($path)) {
             Artisan::call('address:generate-data');
        }
    }

    /**
     * Test that the Thai address data endpoint returns success.
     */
    public function test_thai_address_data_endpoint_returns_success()
    {
        $path = storage_path('app/public/data/thai-address-data.json');
        if (!File::exists($path)) {
            $this->markTestSkipped('Thai address data file could not be generated.');
        }

        $response = $this->get('/thai-addresses');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response->baseResponse);

        $filePath = $response->baseResponse->getFile()->getPathname();

        $this->assertEquals(realpath($path), realpath($filePath));

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $firstItem = $data[0];
        $this->assertArrayHasKey('province_th', $firstItem);
        $this->assertArrayHasKey('district_th', $firstItem);
        $this->assertArrayHasKey('subdistrict_th', $firstItem);
        $this->assertArrayHasKey('zip_code', $firstItem);
    }
}
