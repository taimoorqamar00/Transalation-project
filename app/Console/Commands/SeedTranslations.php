<?php

namespace App\Console\Commands;

use App\Models\Locale;
use App\Models\Translation;
use Illuminate\Console\Command;

class SeedTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         $locale = Locale::first();
         Translation::factory()
        ->count(100000)
        ->create(['locale_id' => $locale->id]);
    $this->info('100k translations seeded');
    }
}
