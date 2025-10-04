<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Article;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PreferencesApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Create some test articles with different sources and categories
        Article::factory()->count(5)->create([
            'source' => 'NewsAPI',
            'category' => 'technology',
        ]);
        
        Article::factory()->count(5)->create([
            'source' => 'The Guardian',
            'category' => 'business',
        ]);
        
        Article::factory()->count(5)->create([
            'source' => 'BBC News',
            'category' => 'sports',
        ]);
    }

    /** @test */
    public function guest_cannot_access_preferences()
    {
        $response = $this->getJson('/api/preferences');
        
        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }

    /** @test */
    public function user_can_get_preferences()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/preferences');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'preferences' => [
                             'sources',
                             'categories',
                             'authors'
                         ],
                         'available_options' => [
                             'sources',
                             'categories',
                             'authors'
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function user_gets_empty_preferences_initially()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/preferences');

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'preferences' => [
                             'sources' => [],
                             'categories' => [],
                             'authors' => []
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function user_can_create_preferences()
    {
        Sanctum::actingAs($this->user);

        $preferences = [
            'sources' => ['NewsAPI', 'The Guardian'],
            'categories' => ['technology', 'business'],
            'authors' => ['Test Author']
        ];

        $response = $this->postJson('/api/preferences', $preferences);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Preferences saved successfully',
                     'data' => $preferences
                 ]);
        
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_update_existing_preferences()
    {
        Sanctum::actingAs($this->user);

        // Create initial preferences
        $this->user->preference()->create([
            'sources' => ['NewsAPI'],
            'categories' => ['technology'],
            'authors' => []
        ]);

        // Update preferences
        $newPreferences = [
            'sources' => ['The Guardian', 'BBC News'],
            'categories' => ['business', 'sports'],
            'authors' => ['New Author']
        ];

        $response = $this->postJson('/api/preferences', $newPreferences);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Preferences saved successfully',
                     'data' => $newPreferences
                 ]);
        
        // Verify updated data
        $this->user->refresh();
        $this->assertEquals($newPreferences['sources'], $this->user->preference->sources);
    }

    /** @test */
    public function preferences_sources_must_be_array()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/preferences', [
            'sources' => 'invalid-not-array',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sources']);
    }

    /** @test */
    public function preferences_categories_must_be_array()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/preferences', [
            'categories' => 'invalid-not-array',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['categories']);
    }

    /** @test */
    public function preferences_accepts_empty_arrays()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/preferences', [
            'sources' => [],
            'categories' => [],
            'authors' => []
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_delete_preferences()
    {
        Sanctum::actingAs($this->user);

        // Create preferences first
        $this->user->preference()->create([
            'sources' => ['NewsAPI'],
            'categories' => ['technology'],
            'authors' => ['Test Author']
        ]);

        $response = $this->deleteJson('/api/preferences');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Preferences deleted successfully'
                 ]);
        
        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_get_personalized_feed()
    {
        Sanctum::actingAs($this->user);

        // Create user preferences
        $this->user->preference()->create([
            'sources' => ['NewsAPI'],
            'categories' => ['technology'],
            'authors' => []
        ]);

        $response = $this->getJson('/api/articles/personalized/feed');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'links',
                     'meta'
                 ]);
        
        // Verify returned articles match preferences
        $articles = $response->json('data');
        foreach ($articles as $article) {
            $matchesPreference = 
                $article['source'] === 'NewsAPI' || 
                $article['category'] === 'technology';
            $this->assertTrue($matchesPreference);
        }
    }

    /** @test */
    public function personalized_feed_returns_message_without_preferences()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/articles/personalized/feed');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'No preferences set. Please set your preferences first.'
                 ]);
    }

    /** @test */
    public function guest_cannot_access_personalized_feed()
    {
        $response = $this->getJson('/api/articles/personalized/feed');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.'
                 ]);
    }

    /** @test */
    public function personalized_feed_respects_pagination()
    {
        Sanctum::actingAs($this->user);

        $this->user->preference()->create([
            'sources' => ['NewsAPI'],
            'categories' => [],
            'authors' => []
        ]);

        $response = $this->getJson('/api/articles/personalized/feed?per_page=3');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertLessThanOrEqual(3, count($data));
    }

    /** @test */
    public function put_method_updates_preferences()
    {
        Sanctum::actingAs($this->user);

        $preferences = [
            'sources' => ['NewsAPI'],
            'categories' => ['technology'],
            'authors' => []
        ];

        $response = $this->putJson('/api/preferences', $preferences);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Preferences saved successfully',
                     'data' => $preferences
                 ]);
    }
}