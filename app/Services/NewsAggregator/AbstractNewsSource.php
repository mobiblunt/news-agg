<?php
namespace App\Services\NewsAggregator;

use App\Contracts\NewsSourceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

abstract class AbstractNewsSource implements NewsSourceInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $sourceName;
    protected int $timeout = 30;

    /**
     * Build the API URL with parameters
     *
     * @param array $params
     * @return string
     */
    abstract protected function buildUrl(array $params): string;

    /**
     * Extract articles array from API response
     *
     * @param array $response
     * @return array
     */
    abstract protected function extractArticles(array $response): array;

    /**
     * Normalize article data
     *
     * @param array $article
     * @return array
     */
    abstract public function normalizeArticle(array $article): array;

    /**
     * Fetch articles from the news source
     *
     * @param array $params
     * @return array
     */
    public function fetchArticles(array $params = []): array
    {
        try {
            $url = $this->buildUrl($params);
            
            Log::info("Fetching from {$this->sourceName}", ['url' => $url]);
            
            $response = Http::timeout($this->timeout)
                           ->retry(3, 1000)
                           ->get($url);

            if ($response->failed()) {
                Log::error("Failed to fetch from {$this->sourceName}", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $articles = $this->extractArticles($response->json());
            
            Log::info("Successfully fetched from {$this->sourceName}", [
                'count' => count($articles)
            ]);

            return $articles;

        } catch (Exception $e) {
            Log::error("Exception fetching from {$this->sourceName}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get the source name
     *
     * @return string
     */
    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    /**
     * Validate required article fields
     *
     * @param array $article
     * @return bool
     */
    protected function isValidArticle(array $article): bool
    {
        return !empty($article['title']) && 
               !empty($article['url']) && 
               !empty($article['published_at']);
    }
}
