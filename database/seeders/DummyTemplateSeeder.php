<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PdfTemplate;

class DummyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pdf_templates')->insert([
            'name' => 'Dummy Template',
            'type' => 'global',
            'file_path' => 'templates/dummy.pdf', // Doesn't need to exist for frontend testing the builder layout mostly
            'field_mapping' => json_encode([
                [
                    'type' => 'db',
                    'key' => 'employeeNameTh',
                    'label' => 'Name (TH)',
                    'x' => 10,
                    'y' => 10,
                    'w' => 15,
                    'h' => 3,
                    'page' => 1,
                    'fontSize' => 12,
                    'autoFit' => True,
                    'align' => 'left'
                ]
            ]),
            'meta_data' => json_encode(['auto_prefix_titles' => false, 'employees_per_page' => 1]),
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
