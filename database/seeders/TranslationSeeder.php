<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Locale;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class TranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create default locales
            $locales = [
                ['code' => 'en', 'name' => 'English'],
                ['code' => 'fr', 'name' => 'French'],
                ['code' => 'es', 'name' => 'Spanish'],
            ];

            foreach ($locales as $locale) {
                Locale::firstOrCreate($locale);
            }

            // Create default tags
            $tags = ['mobile', 'desktop', 'web'];
            
            foreach ($tags as $tag) {
                Tag::firstOrCreate(['name' => $tag]);
            }

            $this->command->info('Default locales and tags created successfully!');
        });
    }
}
