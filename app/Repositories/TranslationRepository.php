<?php

namespace App\Repositories;

use App\Models\Translation;
use Illuminate\Support\Facades\DB;

class TranslationRepository implements TranslationInterface
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $translation = Translation::create([
                'key' => $data['key'],
                'locale_id' => $data['locale_id'],
                'content' => $data['content'],
            ]);

            $translation->tags()->sync($data['tags']);

            return $translation;
        });
    }

    public function update(Translation $translation, array $data)
    {
        $translation->update($data);
        return $translation;
    }

    public function search(array $filters)
    {
        return Translation::query()
            ->with(['locale', 'tags'])
            ->when($filters['key'] ?? null, fn ($q, $v) => $q->where('key', 'like', "%$v%"))
            ->when($filters['content'] ?? null, fn ($q, $v) => $q->where('content', 'like', "%$v%"))
            ->when($filters['locale'] ?? null, fn ($q, $v) =>
                $q->whereHas('locale', fn ($l) => $l->where('code', $v))
            )
            ->when($filters['tag'] ?? null, fn ($q, $v) =>
                $q->whereHas('tags', fn ($t) => $t->where('name', $v))
            )
            ->paginate(20);
    }

    public function exportByLocale(string $locale)
    {
        return Translation::query()
            ->select('key', 'content')
            ->whereHas('locale', fn ($q) => $q->where('code', $locale))
            ->cursor()
            ->pluck('content', 'key');
    }
}
