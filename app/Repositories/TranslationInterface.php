<?php

namespace App\Repositories;

use App\Models\Translation;

interface TranslationInterface
{
    public function create(array $data);
    public function update(Translation $translation, array $data);
    public function search(array $filters);
    public function exportByLocale(string $locale);
}
