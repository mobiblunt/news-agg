<?php

namespace App\Services\NewsAggregator;

use Carbon\Carbon;

class BbcSource extends AbstractNewsSource
{
    public function __construct()
    {
        // BBC doesn't have a public API, so we use NewsAPI with BBC source
        $this->baseUrl = 'https://newsapi.org/v2/top-headlines';
        $this->apiKey = config('services.newsapi.key');
        $this->sourceName = 'BBC News';
    }

    protected function buildUrl(array $params): string
    {
        $defaultParams = [
            'apiKey' => $this->apiKey,
            'sources' => 'bbc-news',
            'pageSize' => 50,
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
        if (empty($article['title']) || empty($article['url'])) {
            return [];
        }

        if (str_contains($article['title'] ?? '', '[Removed]')) {
            return [];
        }

        return [
            'title' => $article['title'],
            'content' => $article['content'] ?? $article['description'] ?? '',
            'author' => $article['author'] ?? 'BBC News',
            'source' => 'BBC News',
            'source_id' => 'bbc_' . md5($article['url']),
            'category' => $this->guessCategory($article['title'] ?? ''),
            'published_at' => Carbon::parse($article['publishedAt'])->toDateTimeString(),
            'url' => $article['url'],
            'image_url' => $article['urlToImage'] ?? null,
        ];
    }

    private function guessCategory(string $title): string
    {
        $title = strtolower($title);
        
        $patterns = [
            'technology' => ['tech', 'ai', 'cyber', 'digital', 'internet'],
            'business' => ['business', 'economy', 'market', 'trade', 'finance'],
            'sports' => ['sport', 'football', 'cricket', 'tennis', 'rugby'],
            'entertainment' => ['film', 'tv', 'music', 'celebrity', 'arts'],
            'science' => ['science', 'space', 'climate', 'research'],
            'health' => ['health', 'nhs', 'medical', 'hospital'],
        ];

        foreach ($patterns as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($title, $keyword)) {
                    return $category;
                }
            }
        }

        return 'general';
    }
}
