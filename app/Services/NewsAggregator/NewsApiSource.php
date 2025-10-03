<?php

namespace App\Services\NewsAggregator;

use Carbon\Carbon;

class NewsApiSource extends AbstractNewsSource
{
    public function __construct()
    {
        $this->baseUrl = 'https://newsapi.org/v2/everything';
        $this->apiKey = config('services.newsapi.key');
        $this->sourceName = 'NewsAPI';
    }

    protected function buildUrl(array $params): string
    {
        $defaultParams = [
            'apiKey' => $this->apiKey,
            'language' => 'en',
            'sortBy' => 'publishedAt',
            'pageSize' => 100,
            'q' => $params['query'] ?? 'technology OR business OR sports',
            'from' => $params['from'] ?? now()->subDays(7)->toIso8601String(),
        ];

        $queryString = http_build_query($defaultParams);
        return "{$this->baseUrl}?{$queryString}";
    }

    protected function extractArticles(array $response): array
    {
        return $response['articles'] ?? [];
    }

    public function normalizeArticle(array $article): array
    {
        // Skip articles without essential data
        if (empty($article['title']) || empty($article['url'])) {
            return [];
        }

        // Remove "[Removed]" articles from NewsAPI
        if (str_contains($article['title'] ?? '', '[Removed]')) {
            return [];
        }

        return [
            'title' => $this->cleanText($article['title']),
            'content' => $this->cleanText($article['content'] ?? $article['description'] ?? ''),
            'author' => $this->cleanText($article['author'] ?? 'Unknown'),
            'source' => 'NewsAPI',
            'source_id' => 'newsapi_' . md5($article['url']),
            'category' => $this->extractCategory($article),
            'published_at' => Carbon::parse($article['publishedAt'])->toDateTimeString(),
            'url' => $article['url'],
            'image_url' => $article['urlToImage'] ?? null,
        ];
    }

    private function cleanText(?string $text): string
    {
        if (!$text) return '';
        
        // Remove common suffixes like "- CNN" or "- 123 chars"
        $text = preg_replace('/\s*-\s*\d+\s*chars?$/i', '', $text);
        $text = preg_replace('/\s*-\s*[A-Z\s]+$/i', '', $text);
        
        return trim($text) ?: '';
    }

    private function extractCategory(array $article): string
    {
        // Try to extract category from source name or content
        $source = $article['source']['name'] ?? '';
        $title = strtolower($article['title'] ?? '');
        
        $categories = [
            'technology' => ['tech', 'ai', 'software', 'computer', 'digital'],
            'business' => ['business', 'economy', 'finance', 'market', 'stock'],
            'sports' => ['sport', 'football', 'basketball', 'soccer', 'nfl'],
            'entertainment' => ['entertainment', 'movie', 'music', 'celebrity', 'hollywood'],
            'health' => ['health', 'medical', 'medicine', 'disease', 'covid'],
            'science' => ['science', 'research', 'study', 'space', 'nasa'],
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($title, $keyword)) {
                    return $category;
                }
            }
        }

        return 'general';
    }
}
