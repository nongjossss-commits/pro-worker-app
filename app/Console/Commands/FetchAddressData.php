<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;

class FetchAddressData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-address-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the latest Thai address data from the source repository and saves it locally.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fetch Thai address data...');

        // This is the new, correct URL for the data source.
        $sourceUrl = 'https://raw.githubusercontent.com/kongvut/thai-province-data/refs/heads/master/api/latest/province_with_district_and_sub_district.json';

        // The destination path inside the 'public' folder.
        $destinationPath = 'public/data/thai-address-data.json';

        try {
            $response = Http::timeout(60)->get($sourceUrl);

            if ($response->successful()) {
                $file = new Filesystem();

                // Ensure the 'public/data' directory exists.
                $directoryPath = storage_path('app/public/data');
                if (!$file->isDirectory($directoryPath)) {
                    $file->makeDirectory($directoryPath, 0755, true);
                }

                // Save the file using the 'public' disk.
                Storage::disk('public')->put('data/thai-address-data.json', $response->body());

                $this->info('Successfully fetched and saved address data to:');
                $this->comment(storage_path('app/' . $destinationPath));
                return self::SUCCESS;
            } else {
                $this->error('Failed to fetch data from the source URL. Status code: ' . $response->status());
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}