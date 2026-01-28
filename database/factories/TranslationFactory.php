<?php

namespace Database\Factories;

use App\Models\Locale;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->word . '.' . $this->faker->word . '.' . uniqid(),
            'content' => $this->faker->sentence,
            'locale_id' => Locale::factory(),
        ];
    }
}
