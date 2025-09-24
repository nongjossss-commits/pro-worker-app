<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DownloadAddressData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'address:download-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads the Thai address data from the source repository.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = 'https://raw.githubusercontent.com/kongvut/thai-province-data/master/api/v1/province_with_amphure_tambon.json';
        $directory = public_path('data');
        $filePath = $directory . '/thai-address-data.json';

        $this->info('Starting download of Thai address data...');

        // Ensure the directory exists
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
            $this->comment('Created directory: ' . $directory);
        }

        try {
            $response = Http::timeout(300)->get($url);

            if ($response->successful()) {
                File::put($filePath, $response->body());
                $this->info('Successfully downloaded and saved address data to: ' . $filePath);
            } else {
                $this->error('Failed to download address data. Server responded with status code: ' . $response->status());
                return 1; // Indicate failure
            }
        } catch (\Exception $e) {
            $this->error('An error occurred during download: ' . $e->getMessage());
            return 1; // Indicate failure
        }

        return 0; // Indicate success
    }
}
