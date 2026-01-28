<?php

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user for testing
        $this->user = User::factory()->create();
    }

    public function test_create_translation_api()
    {
        $locale = Locale::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/translations', [
            'key' => 'home.title',
            'content' => 'Home',
            'locale_id' => $locale->id,
            'tags' => [$tag->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['key' => 'home.title']);
    }

    public function test_search_translation()
    {
        Translation::factory()->create([
            'key' => 'dashboard.title',
            'content' => 'Dashboard',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/translations/search?key=dashboard');

        $response->assertStatus(200)
            ->assertJsonFragment(['dashboard.title']);
    }

    public function test_export_translation()
    {
        $locale = Locale::factory()->create(['code' => 'en']);

        Translation::factory()->count(3)->create([
            'locale_id' => $locale->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/translations/export?locale=en');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }
}

