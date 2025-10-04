<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed some test data
        Article::factory()->count(20)->create();
    }

    /** @test */
    public function it_can_get_all_articles()
    {
        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'title',
                             'content',
                             'author',
                             'source',
                             'category',
                             'published_at',
                             'url',
                             'image_url'
                         ]
                     ],
                     'links',
                     'meta'
                 ]);
    }

    /** @test */
    public function it_can_search_articles()
    {
        Article::factory()->create([
            'title' => 'Laravel Framework Tutorial',
            'content' => 'Learn Laravel framework'
        ]);

        Article::factory()->create([
            'title' => 'React Components Guide',
            'content' => 'Building React components'
        ]);

        $response = $this->getJson('/api/articles?search=Laravel');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        
        // Check if the search result contains Laravel
        $foundLaravel = false;
        foreach ($data as $article) {
            if (stripos($article['title'], 'Laravel') !== false || 
                stripos($article['content'], 'Laravel') !== false) {
                $foundLaravel = true;
                break;
            }
        }
        $this->assertTrue($foundLaravel);
    }

    /** @test */
    public function it_can_filter_by_source()
    {
        Article::factory()->create(['source' => 'NewsAPI', 'title' => 'Test Article 1']);
        Article::factory()->create(['source' => 'The Guardian', 'title' => 'Test Article 2']);

        $response = $this->getJson('/api/articles?source=NewsAPI');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (!empty($data)) {
            foreach ($data as $article) {
                $this->assertEquals('NewsAPI', $article['source']);
            }
        }
    }

    /** @test */
    public function it_can_filter_by_category()
    {
        Article::factory()->create([
            'category' => 'technology',
            'title' => 'Tech Article'
        ]);
        
        Article::factory()->create([
            'category' => 'business',
            'title' => 'Business Article'
        ]);

        $response = $this->getJson('/api/articles?category=technology');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (!empty($data)) {
            foreach ($data as $article) {
                $this->assertEquals('technology', $article['category']);
            }
        }
    }

    /** @test */
    public function it_can_filter_by_date()
    {
        Article::factory()->create([
            'published_at' => now()->subDays(10),
            'title' => 'Old Article'
        ]);
        
        Article::factory()->create([
            'published_at' => now()->subDay(),
            'title' => 'Recent Article'
        ]);

        $date = now()->subDays(5)->toDateString();
        $response = $this->getJson("/api/articles?date={$date}");

        $response->assertStatus(200);
        
        // All returned articles should be after the specified date
        $data = $response->json('data');
        foreach ($data as $article) {
            $publishedDate = \Carbon\Carbon::parse($article['published_at']);
            $this->assertTrue($publishedDate->gte($date));
        }
    }

    /** @test */
    public function it_handles_pagination()
    {
        $response = $this->getJson('/api/articles?per_page=5&page=1');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'links' => [
                         'first',
                         'last',
                         'prev',
                         'next'
                     ],
                     'meta' => [
                         'current_page',
                         'per_page',
                         'total'
                     ]
                 ]);
        
        $data = $response->json('data');
        $this->assertLessThanOrEqual(5, count($data));
    }

    /** @test */
    public function it_can_get_single_article()
    {
        $article = Article::factory()->create();

        $response = $this->getJson("/api/articles/{$article->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $article->id,
                         'title' => $article->title,
                     ]
                 ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_article()
    {
        $response = $this->getJson('/api/articles/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_sort_articles()
    {
        // Create articles with specific titles
        Article::factory()->create(['title' => 'AAA First Article']);
        Article::factory()->create(['title' => 'ZZZ Last Article']);

        $response = $this->getJson('/api/articles?sort_by=title&sort_order=asc&per_page=100');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        if (count($data) >= 2) {
            // Find our specific articles
            $titles = array_column($data, 'title');
            $aaaIndex = array_search('AAA First Article', $titles);
            $zzzIndex = array_search('ZZZ Last Article', $titles);
            
            if ($aaaIndex !== false && $zzzIndex !== false) {
                $this->assertLessThan($zzzIndex, $aaaIndex);
            }
        }
    }

    /** @test */
    public function it_can_filter_by_multiple_sources()
    {
        Article::factory()->create(['source' => 'NewsAPI']);
        Article::factory()->create(['source' => 'The Guardian']);
        Article::factory()->create(['source' => 'BBC News']);

        $response = $this->getJson('/api/articles?sources=NewsAPI,The Guardian');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        foreach ($data as $article) {
            $this->assertContains($article['source'], ['NewsAPI', 'The Guardian']);
        }
    }

    /** @test */
    public function it_can_combine_multiple_filters()
    {
        Article::factory()->create([
            'source' => 'NewsAPI',
            'category' => 'technology',
            'title' => 'AI Technology News'
        ]);

        $response = $this->getJson('/api/articles?source=NewsAPI&category=technology&search=AI');

        $response->assertStatus(200);
    }
}
