<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Locale;
use App\Models\Translation;
use App\Models\User;

class TranslationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user for testing
        $this->user = User::factory()->create();
    }

    public function test_export_performance()
    {
        $locale = Locale::factory()->create(['code' => 'en']);

        Translation::factory()->count(1000)->create([
            'locale_id' => $locale->id,
        ]);

        $start = microtime(true);

        $this->actingAs($this->user, 'sanctum')->getJson('/api/translations/export?locale=en');

        $time = microtime(true) - $start;

        $this->assertTrue(
            $time < 0.5,
            "Export took too long: {$time}s"
        );
    }
}
