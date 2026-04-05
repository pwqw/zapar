<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('media_path', '');

        // Welcome message sample
        Setting::set('welcome_message', 'Hello, <br><br> this is our {example page} thanks');
        Setting::set('welcome_message_variables', [
            [
                'name' => 'example page',
                'url' => '/#/document/example',
            ],
        ]);

        // Embedded Google Doc pages
        Setting::set('google_doc_pages', [
            [
                'title' => 'Example page',
                'slug' => 'example',
                'embed_url' => 'https://docs.google.com/document/d/e/2PACX-1vTEVO1c95O58sfwIhr7tWSh0Ge1zrB3qhc9XB28jANOw2iAaUVDnsJ9MWKaJBKDNJ4CMfd6uBvtz68T/pub',
                'default_back_url' => null,
            ],
        ]);
    }
}
