<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class GenerateThaiAddressData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'address:generate-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download and generate Thai address data JSON file from thailand-geography-json repository';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Downloading Thai address data...');

        // URL for raw JSON data
        $url = 'https://raw.githubusercontent.com/thailand-geography-data/thailand-geography-json/main/src/geography.json';

        try {
            // Fetch data
            $items = Http::get($url)->json();

            if (!$items) {
                $this->error('Failed to download data file.');
                return 1;
            }

            $this->info('Processing data...');

            $result = [];

            foreach ($items as $item) {
                $result[] = [
                    'province_th' => $item['provinceNameTh'],
                    'province_en' => $item['provinceNameEn'],
                    'district_th' => $item['districtNameTh'],
                    'district_en' => $item['districtNameEn'],
                    'subdistrict_th' => $item['subdistrictNameTh'],
                    'subdistrict_en' => $item['subdistrictNameEn'],
                    'zip_code' => (string) $item['postalCode'],
                ];
            }

            // Path to save the file
            $path = storage_path('app/public/data/thai-address-data.json');

            // Ensure directory exists
            $directory = dirname($path);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Save to file
            File::put($path, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            $this->info("Successfully generated Thai address data at: {$path}");
            $this->info('Total entries: ' . count($result));

            return 0;

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return 1;
        }
    }
}
