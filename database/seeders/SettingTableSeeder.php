<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('media_path', '');

        // Configuración de mensaje de bienvenida
        Setting::set('welcome_message', 'Hola, <br><br> esta es nuestra {página de ejemplo} gracias');
        Setting::set('welcome_message_variables', [
            [
                'name' => 'página de ejemplo',
                'url' => '/#/document/ejemplo',
            ],
        ]);

        // Configuración de páginas embebidas (Google Docs)
        Setting::set('google_doc_pages', [
            [
                'title' => 'Página de ejemplo',
                'slug' => 'ejemplo',
                'embed_url' => 'https://docs.google.com/document/d/e/2PACX-1vTEVO1c95O58sfwIhr7tWSh0Ge1zrB3qhc9XB28jANOw2iAaUVDnsJ9MWKaJBKDNJ4CMfd6uBvtz68T/pub',
                'default_back_url' => null,
            ],
        ]);
    }
}
