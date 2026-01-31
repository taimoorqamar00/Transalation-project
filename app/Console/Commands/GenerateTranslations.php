<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Locale;
use App\Models\Translation;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:generate 
                            {--count=100000 : Number of translations to generate}
                            {--locales=3 : Number of locales to create}
                            {--tags=5 : Number of tags to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test translations for scalability testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $localeCount = (int) $this->option('locales');
        $tagCount = (int) $this->option('tags');

        $this->info('Generating test translations...');

        DB::transaction(function () use ($count, $localeCount, $tagCount) {
            // Create locales
            $locales = collect(['en', 'fr', 'es', 'de', 'it', 'pt', 'nl', 'sv', 'no', 'da'])
                ->take($localeCount)
                ->map(function ($code, $index) {
                    return Locale::updateOrCreate(
                        ['code' => $code],
                        ['name' => ucfirst($code)]
                    );
                });

            // Create tags
            $tags = collect(['mobile', 'desktop', 'web', 'api', 'admin'])
                ->take($tagCount)
                ->map(function ($name) {
                    return Tag::firstOrCreate(['name' => $name]);
                });

            // Generate translation keys
            $baseKeys = [
                'welcome', 'goodbye', 'hello', 'thank_you', 'please', 'sorry', 'yes', 'no',
                'login', 'logout', 'register', 'profile', 'settings', 'dashboard', 'home',
                'about', 'contact', 'help', 'support', 'documentation', 'tutorial', 'guide',
                'error', 'success', 'warning', 'info', 'loading', 'saving', 'deleted', 'updated',
                'create', 'edit', 'delete', 'save', 'cancel', 'submit', 'search', 'filter',
                'sort', 'asc', 'desc', 'page', 'next', 'previous', 'first', 'last', 'total'
            ];

            $this->info("Generating {$count} translations...");

            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            $translations = [];
            $tagRelations = [];

            for ($i = 0; $i < $count; $i++) {
                $key = $baseKeys[array_rand($baseKeys)] . '_' . $i;
                $locale = $locales->random();
                
                $translations[] = [
                    'key' => $key,
                    'locale_id' => $locale->id,
                    'content' => Str::title(str_replace('_', ' ', $key)) . ' in ' . $locale->code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Insert in batches for better performance
                if (count($translations) >= 1000) {
                    Translation::insert($translations);
                    $translations = [];
                }

                $progressBar->advance();
            }

            // Insert remaining translations
            if (!empty($translations)) {
                Translation::insert($translations);
            }

            // Attach random tags to translations
            $this->info("\nAttaching tags to translations...");
            $allTranslations = Translation::all(['id']);
            
            foreach ($allTranslations as $translation) {
                $randomTags = $tags->random(rand(1, min(3, $tags->count())));
                $translation->tags()->attach($randomTags->pluck('id'));
            }

            $progressBar->finish();
        });

        $this->info("\n✅ Successfully generated {$count} translations!");
        $this->info('📊 Summary:');
        $this->info('   - Locales: ' . Locale::count());
        $this->info('   - Tags: ' . Tag::count());
        $this->info('   - Translations: ' . Translation::count());

        return Command::SUCCESS;
    }
}
