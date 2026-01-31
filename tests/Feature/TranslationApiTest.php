<?php

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Locale $locale;
    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->locale = Locale::factory()->create(['code' => 'en']);
        $this->tag = Tag::factory()->create(['name' => 'web']);
    }

    public function test_unauthenticated_requests_are_blocked(): void
    {
        $this->postJson('/api/translations', [])->assertStatus(401);
        $this->getJson('/api/translations/1')->assertStatus(401);
        $this->putJson('/api/translations/1', [])->assertStatus(401);
        $this->deleteJson('/api/translations/1')->assertStatus(401);
        $this->getJson('/api/translations/search')->assertStatus(401);
        $this->getJson('/api/translations/export')->assertStatus(401);
    }

    public function test_create_translation_successfully(): void
    {
        $data = [
            'key' => 'home.title',
            'content' => 'Home',
            'locale_id' => $this->locale->id,
            'tags' => [$this->tag->id],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'key',
                    'content',
                    'locale',
                    'tags'
                ]
            ])
            ->assertJsonFragment(['key' => 'home.title']);

        $this->assertDatabaseHas('translations', [
            'key' => 'home.title',
            'content' => 'Home',
            'locale_id' => $this->locale->id
        ]);
    }

    public function test_create_translation_validation_fails(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key', 'locale_id', 'content']);
    }

    public function test_create_translation_with_invalid_locale(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => 'test.key',
                'content' => 'Test',
                'locale_id' => 999
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale_id']);
    }

    public function test_show_translation_successfully(): void
    {
        $translation = Translation::factory()->create([
            'locale_id' => $this->locale->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'key',
                    'content',
                    'locale',
                    'tags'
                ]
            ])
            ->assertJsonFragment(['id' => $translation->id]);
    }

    public function test_show_nonexistent_translation_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/999');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Translation not found']);
    }

    public function test_update_translation_successfully(): void
    {
        $translation = Translation::factory()->create([
            'locale_id' => $this->locale->id
        ]);

        $data = [
            'key' => 'updated.key',
            'content' => 'Updated content',
            'tags' => [$this->tag->id]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/translations/{$translation->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['key' => 'updated.key']);

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'key' => 'updated.key',
            'content' => 'Updated content'
        ]);
    }

    public function test_update_nonexistent_translation_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/translations/999', [
                'key' => 'test.key',
                'content' => 'Test'
            ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Translation not found']);
    }

    public function test_delete_translation_successfully(): void
    {
        $translation = Translation::factory()->create([
            'locale_id' => $this->locale->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/translations/{$translation->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('translations', ['id' => $translation->id]);
    }

    public function test_delete_nonexistent_translation_returns_404(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/translations/999');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Translation not found']);
    }

    public function test_search_translations_by_key(): void
    {
        Translation::factory()->create([
            'key' => 'dashboard.title',
            'content' => 'Dashboard',
            'locale_id' => $this->locale->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/search?key=dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ]);
    }

    public function test_search_translations_by_content(): void
    {
        Translation::factory()->create([
            'content' => 'Welcome to our application',
            'locale_id' => $this->locale->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/search?content=Welcome');

        $response->assertStatus(200);
    }

    public function test_search_translations_by_locale(): void
    {
        Translation::factory()->create(['locale_id' => $this->locale->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/search?locale=en');

        $response->assertStatus(200);
    }

    public function test_search_translations_by_tag(): void
    {
        $translation = Translation::factory()->create(['locale_id' => $this->locale->id]);
        $translation->tags()->attach($this->tag);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/search?tag=web');

        $response->assertStatus(200);
    }

    public function test_search_with_pagination(): void
    {
        Translation::factory()->count(25)->create(['locale_id' => $this->locale->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/search?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('data.per_page', 10)
            ->assertJsonPath('data.total', 25);
    }

    public function test_export_translations_by_locale(): void
    {
        Translation::factory()->count(3)->create([
            'locale_id' => $this->locale->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data'
            ])
            ->assertHeader('Cache-Control', 'public, max-age=3600');

        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    public function test_export_requires_locale_parameter(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_export_invalid_locale_returns_error(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['locale']);
    }

    public function test_export_caches_results(): void
    {
        Translation::factory()->create([
            'key' => 'test.key',
            'content' => 'Test content',
            'locale_id' => $this->locale->id
        ]);

        // First request
        $response1 = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=en');
        
        // Second request should be faster due to caching
        $response2 = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=en');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        $this->assertEquals($response1->json('data'), $response2->json('data'));
    }

    public function test_create_translation_clears_export_cache(): void
    {
        // Create initial cache
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=en');
        
        $this->assertTrue(Cache::has('translations_export_en'));

        // Create new translation
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => 'new.key',
                'content' => 'New content',
                'locale_id' => $this->locale->id
            ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has('translations_export_en'));
    }
}

