<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\TranslationInterface;

class TranslationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TranslationInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = app(TranslationInterface::class);
    }

    public function test_it_creates_translation()
    {
        $locale = Locale::factory()->create();
        $tag = Tag::factory()->create();

        $translation = $this->repo->create([
            'key' => 'auth.login',
            'content' => 'Login',
            'locale_id' => $locale->id,
            'tags' => [$tag->id],
        ]);

        $this->assertDatabaseHas('translations', [
            'key' => 'auth.login',
            'content' => 'Login',
        ]);

        $this->assertCount(1, $translation->tags);
    }

    public function test_it_exports_by_locale()
    {
        $locale = Locale::factory()->create(['code' => 'en']);

        Translation::factory()->count(5)->create([
            'locale_id' => $locale->id,
        ]);

        $result = $this->repo->exportByLocale('en');

        $this->assertCount(5, $result);
    }
}
