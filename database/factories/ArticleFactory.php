<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sources = ['NewsAPI', 'The Guardian', 'BBC News'];
        $categories = ['technology', 'business', 'sports', 'entertainment', 'health', 'science', 'general'];
        
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'author' => fake()->name(),
            'source' => fake()->randomElement($sources),
            'source_id' => fake()->unique()->lexify('??????') . '_' . fake()->unique()->numerify('####'),
            'category' => fake()->randomElement($categories),
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'url' => fake()->url(),
            'image_url' => fake()->boolean(70) ? fake()->imageUrl(640, 480, 'news') : null,
        ];
    }

    /**
     * Indicate that the article is from NewsAPI.
     */
    public function newsapi(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'NewsAPI',
            'source_id' => 'newsapi_' . fake()->unique()->uuid(),
        ]);
    }

    /**
     * Indicate that the article is from The Guardian.
     */
    public function guardian(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'The Guardian',
            'source_id' => 'guardian_' . fake()->unique()->uuid(),
        ]);
    }

    /**
     * Indicate that the article is from BBC News.
     */
    public function bbc(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'BBC News',
            'source_id' => 'bbc_' . fake()->unique()->uuid(),
        ]);
    }

    /**
     * Indicate that the article is in technology category.
     */
    public function technology(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'technology',
        ]);
    }

    /**
     * Indicate that the article is in business category.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'business',
        ]);
    }

    /**
     * Indicate that the article was published recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => Carbon::now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    /**
     * Indicate that the article is old.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => Carbon::now()->subDays(fake()->numberBetween(30, 90)),
        ]);
    }
}
