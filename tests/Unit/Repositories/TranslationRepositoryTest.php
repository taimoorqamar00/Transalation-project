<?php

namespace Tests\Unit\Repositories;

use App\Models\Translation;
use App\Models\Locale;
use App\Models\Tag;
use App\Repositories\TranslationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TranslationRepository $repository;
    private Locale $locale;
    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new TranslationRepository();
        $this->locale = Locale::factory()->create(['code' => 'en']);
        $this->tag = Tag::factory()->create(['name' => 'web']);
    }

    public function test_create_translation_with_tags(): void
    {
        $data = [
            'key' => 'test.key',
            'locale_id' => $this->locale->id,
            'content' => 'Test content',
            'tags' => [$this->tag->id]
        ];

        $translation = $this->repository->create($data);

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertEquals($data['key'], $translation->key);
        $this->assertEquals($data['content'], $translation->content);
        $this->assertCount(1, $translation->tags);
        $this->assertEquals($this->tag->id, $translation->tags->first()->id);
    }

    public function test_create_translation_without_tags(): void
    {
        $data = [
            'key' => 'test.key',
            'locale_id' => $this->locale->id,
            'content' => 'Test content'
        ];

        $translation = $this->repository->create($data);

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertCount(0, $translation->tags);
    }

    public function test_update_translation(): void
    {
        $translation = Translation::factory()->create(['locale_id' => $this->locale->id]);
        
        $data = [
            'key' => 'updated.key',
            'content' => 'Updated content',
            'tags' => [$this->tag->id]
        ];

        $updated = $this->repository->update($translation, $data);

        $this->assertEquals($data['key'], $updated->key);
        $this->assertEquals($data['content'], $updated->content);
        $this->assertCount(1, $updated->tags);
    }

    public function test_delete_translation(): void
    {
        $translation = Translation::factory()->create(['locale_id' => $this->locale->id]);

        $result = $this->repository->delete($translation);

        $this->assertTrue($result);
        $this->assertSoftDeleted('translations', ['id' => $translation->id]);
    }

    public function test_find_by_id(): void
    {
        $translation = Translation::factory()->create(['locale_id' => $this->locale->id]);

        $found = $this->repository->findById($translation->id);

        $this->assertInstanceOf(Translation::class, $found);
        $this->assertEquals($translation->id, $found->id);
        $this->assertTrue($found->relationLoaded('locale'));
        $this->assertTrue($found->relationLoaded('tags'));
    }

    public function test_find_by_id_returns_null_for_nonexistent(): void
    {
        $found = $this->repository->findById(999);

        $this->assertNull($found);
    }

    public function test_search_by_key(): void
    {
        Translation::factory()->create([
            'key' => 'welcome.message',
            'locale_id' => $this->locale->id
        ]);

        $results = $this->repository->search(['key' => 'welcome']);

        $this->assertCount(1, $results);
        $this->assertEquals('welcome.message', $results->first()->key);
    }

    public function test_search_by_content(): void
    {
        Translation::factory()->create([
            'content' => 'Welcome to our application',
            'locale_id' => $this->locale->id
        ]);

        $results = $this->repository->search(['content' => 'Welcome']);

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Welcome', $results->first()->content);
    }

    public function test_search_by_locale(): void
    {
        $frenchLocale = Locale::factory()->create(['code' => 'fr']);
        Translation::factory()->create(['locale_id' => $this->locale->id]);
        Translation::factory()->create(['locale_id' => $frenchLocale->id]);

        $results = $this->repository->search(['locale' => 'en']);

        $this->assertCount(1, $results);
        $this->assertEquals('en', $results->first()->locale->code);
    }

    public function test_search_by_tag(): void
    {
        $translation = Translation::factory()->create(['locale_id' => $this->locale->id]);
        $translation->tags()->attach($this->tag);

        $results = $this->repository->search(['tag' => 'web']);

        $this->assertCount(1, $results);
        $this->assertEquals($translation->id, $results->first()->id);
    }

    public function test_export_by_locale_caches_results(): void
    {
        Translation::factory()->create([
            'key' => 'test.key',
            'content' => 'Test content',
            'locale_id' => $this->locale->id
        ]);

        $cacheKey = 'translations_export_en';
        
        // First call should cache the results
        $result1 = $this->repository->exportByLocale('en');
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should return cached results
        $result2 = $this->repository->exportByLocale('en');
        
        $this->assertEquals($result1, $result2);
        $this->assertArrayHasKey('test.key', $result1);
        $this->assertEquals('Test content', $result1['test.key']);
    }

    public function test_export_by_locale_clears_cache_on_create(): void
    {
        $cacheKey = 'translations_export_en';
        
        // Create initial cache
        $this->repository->exportByLocale('en');
        $this->assertTrue(Cache::has($cacheKey));

        // Create new translation
        $this->repository->create([
            'key' => 'new.key',
            'content' => 'New content',
            'locale_id' => $this->locale->id
        ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_search_pagination(): void
    {
        Translation::factory()->count(25)->create(['locale_id' => $this->locale->id]);

        $results = $this->repository->search(['per_page' => 10]);

        $this->assertEquals(10, $results->perPage());
        $this->assertEquals(25, $results->total());
        $this->assertEquals(3, $results->lastPage());
    }
}
