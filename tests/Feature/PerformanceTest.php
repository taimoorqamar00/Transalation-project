<?php

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Locale $locale;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->locale = Locale::factory()->create(['code' => 'en']);
    }

    public function test_export_performance_with_large_dataset(): void
    {
        // Create 10,000 translations
        Translation::factory()->count(10000)->create([
            'locale_id' => $this->locale->id
        ]);

        $startTime = microtime(true);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=en');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Should complete in under 500ms as per requirements
        $this->assertLessThan(500, $responseTime, 
            "Export took {$responseTime}ms, should be under 500ms");
    }

    public function test_export_cached_performance(): void
    {
        // Create translations and cache them
        Translation::factory()->count(5000)->create([
            'locale_id' => $this->locale->id
        ]);

        // First request to cache
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=en');

        // Test cached performance
        $startTime = microtime(true);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=en');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // Cached response should be much faster
        $this->assertLessThan(50, $responseTime, 
            "Cached export took {$responseTime}ms, should be under 50ms");
    }

    public function test_search_performance_with_large_dataset(): void
    {
        // Create 10,000 translations with varied keys
        Translation::factory()->count(10000)->create([
            'locale_id' => $this->locale->id
        ]);

        $startTime = microtime(true);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/search?key=test&per_page=20');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // Search should complete in under 200ms as per requirements
        $this->assertLessThan(200, $responseTime, 
            "Search took {$responseTime}ms, should be under 200ms");
    }

    public function test_create_translation_performance(): void
    {
        $startTime = microtime(true);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => 'performance.test',
                'content' => 'Performance test content',
                'locale_id' => $this->locale->id
            ]);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(201);
        
        // Create should complete in under 200ms
        $this->assertLessThan(200, $responseTime, 
            "Create took {$responseTime}ms, should be under 200ms");
    }

    public function test_database_query_optimization(): void
    {
        // Enable query logging
        DB::enableQueryLog();
        
        Translation::factory()->count(1000)->create([
            'locale_id' => $this->locale->id
        ]);

        // Test search query count
        DB::flushQueryLog();
        
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/search?per_page=20');
        
        $queries = DB::getQueryLog();
        
        // Should use minimal queries due to eager loading
        $this->assertLessThanOrEqual(3, count($queries), 
            "Too many queries executed: " . count($queries));
        
        DB::disableQueryLog();
    }

    public function test_memory_usage_during_export(): void
    {
        $memoryBefore = memory_get_usage(true);
        
        // Create a large dataset
        Translation::factory()->count(5000)->create([
            'locale_id' => $this->locale->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=en');
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

        $response->assertStatus(200);
        
        // Memory usage should be reasonable (under 50MB for this operation)
        $this->assertLessThan(50, $memoryUsed, 
            "Memory usage: {$memoryUsed}MB, should be under 50MB");
    }

    public function test_concurrent_requests_performance(): void
    {
        Translation::factory()->count(1000)->create([
            'locale_id' => $this->locale->id
        ]);

        $startTime = microtime(true);
        
        // Simulate multiple concurrent requests
        $responses = collect(range(1, 5))->map(function () {
            return $this->actingAs($this->user, 'sanctum')
                ->getJson('/api/translations/search?per_page=10');
        });
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // All requests should complete successfully
        $responses->each(function ($response) {
            $response->assertStatus(200);
        });
        
        // Average time per request should be reasonable
        $averageTime = $totalTime / 5;
        $this->assertLessThan(300, $averageTime, 
            "Average concurrent request time: {$averageTime}ms, should be under 300ms");
    }

    public function test_cache_invalidation_performance(): void
    {
        // Create initial cache
        Translation::factory()->create(['locale_id' => $this->locale->id]);
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/export?locale=en');

        $startTime = microtime(true);
        
        // Create new translation (should clear cache)
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations', [
                'key' => 'cache.test',
                'content' => 'Cache test',
                'locale_id' => $this->locale->id
            ]);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        // Cache invalidation should be fast
        $this->assertLessThan(200, $responseTime, 
            "Cache invalidation took {$responseTime}ms, should be under 200ms");
        
        // Verify cache was cleared
        $this->assertFalse(Cache::has('translations_export_en'));
    }
}
