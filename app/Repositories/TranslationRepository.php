<?php

namespace App\Repositories;

use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TranslationRepository implements TranslationInterface
{
    private const CACHE_TTL = 3600;
    private const EXPORT_CACHE_PREFIX = 'translations_export_';

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $translation = Translation::create([
                'key' => $data['key'],
                'locale_id' => $data['locale_id'],
                'content' => $data['content'],
            ]);

            if (isset($data['tags'])) {
                $translation->tags()->sync($data['tags']);
            }

            $this->clearExportCache($translation->locale->code ?? null);

            return $translation->load(['locale', 'tags']);
        });
    }

    public function update(Translation $translation, array $data)
    {
        return DB::transaction(function () use ($translation, $data) {
            $translation->update([
                'key' => $data['key'] ?? $translation->key,
                'content' => $data['content'] ?? $translation->content,
            ]);

            if (isset($data['tags'])) {
                $translation->tags()->sync($data['tags']);
            }

            $this->clearExportCache($translation->locale->code ?? null);

            return $translation->load(['locale', 'tags']);
        });
    }

    public function delete(Translation $translation): bool
    {
        $localeCode = $translation->locale->code ?? null;
        $result = $translation->delete();
        
        if ($result) {
            $this->clearExportCache($localeCode);
        }
        
        return $result;
    }

    public function findById(int $id)
    {
        return Translation::with(['locale', 'tags'])->find($id);
    }

    public function search(array $filters)
    {
        $query = Translation::with(['locale', 'tags']);

        if (!empty($filters['key'])) {
            $query->where('key', 'like', '%' . $filters['key'] . '%');
        }

        if (!empty($filters['content'])) {
            $query->where('content', 'like', '%' . $filters['content'] . '%');
        }

        if (!empty($filters['locale'])) {
            $query->whereHas('locale', function ($q) use ($filters) {
                $q->where('code', $filters['locale']);
            });
        }

        if (!empty($filters['tag'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('name', $filters['tag']);
            });
        }

        return $query->paginate(
            $filters['per_page'] ?? 20
        );
    }

    public function exportByLocale(string $locale)
    {
        $cacheKey = self::EXPORT_CACHE_PREFIX . $locale;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            return Translation::query()
                ->select('key', 'content')
                ->whereHas('locale', function ($query) use ($locale) {
                    $query->where('code', $locale);
                })
                ->orderBy('key')
                ->pluck('content', 'key')
                ->toArray();
        });
    }

    private function clearExportCache(?string $localeCode): void
    {
        if ($localeCode) {
            Cache::forget(self::EXPORT_CACHE_PREFIX . $localeCode);
        }
    }
}
