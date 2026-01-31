<?php

namespace Tests\Unit\Models;

use App\Models\Translation;
use App\Models\Locale;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    private Translation $translation;
    private Locale $locale;
    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->locale = Locale::factory()->create();
        $this->tag = Tag::factory()->create();
        $this->translation = Translation::factory()->create([
            'locale_id' => $this->locale->id
        ]);
    }

    public function test_translation_can_be_created(): void
    {
        $this->assertInstanceOf(Translation::class, $this->translation);
        $this->assertDatabaseHas('translations', [
            'key' => $this->translation->key,
            'locale_id' => $this->locale->id,
            'content' => $this->translation->content
        ]);
    }

    public function test_translation_belongs_to_locale(): void
    {
        $this->assertInstanceOf(Locale::class, $this->translation->locale);
        $this->assertEquals($this->locale->id, $this->translation->locale->id);
    }

    public function test_translation_can_have_many_tags(): void
    {
        $this->translation->tags()->attach($this->tag);
        
        $this->assertCount(1, $this->translation->tags);
        $this->assertInstanceOf(Tag::class, $this->translation->tags->first());
    }

    public function test_translation_can_detach_tags(): void
    {
        $this->translation->tags()->attach($this->tag);
        $this->translation->tags()->detach($this->tag);
        
        $this->assertCount(0, $this->translation->tags);
    }

    public function test_translation_key_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Translation::factory()->create(['key' => null]);
    }

    public function test_translation_locale_id_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Translation::factory()->create(['locale_id' => null]);
    }

    public function test_translation_content_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Translation::factory()->create(['content' => null]);
    }

    public function test_translation_key_locale_combination_is_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Translation::factory()->create([
            'key' => $this->translation->key,
            'locale_id' => $this->locale->id
        ]);
    }

    public function test_translation_can_be_soft_deleted(): void
    {
        $this->translation->delete();
        
        $this->assertSoftDeleted('translations', [
            'id' => $this->translation->id
        ]);
    }

    public function test_translation_fillable_attributes(): void
    {
        $data = [
            'key' => 'test.key',
            'locale_id' => $this->locale->id,
            'content' => 'Test content'
        ];

        $translation = new Translation($data);
        $this->assertEquals($data['key'], $translation->key);
        $this->assertEquals($data['locale_id'], $translation->locale_id);
        $this->assertEquals($data['content'], $translation->content);
    }
}
