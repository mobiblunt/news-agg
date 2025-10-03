<?php

namespace App\Services\NewsAggregator;

use Carbon\Carbon;

class GuardianSource extends AbstractNewsSource
{
    public function __construct()
    {
        $this->baseUrl = 'https://content.guardianapis.com/search';
        $this->apiKey = config('services.guardian.key');
        $this->sourceName = 'The Guardian';
    }

    protected function buildUrl(array $params): string
    {
        $defaultParams = [
            'api-key' => $this->apiKey,
            'show-fields' => 'all',
            'page-size' => 50,
            'order-by' => 'newest',
            'from-date' => $params['from'] ?? now()->subDays(7)->toDateString(),
        ];

        if (isset($params['section'])) {
            $defaultParams['section'] = $params['section'];
        }

        $queryString = http_build_query($defaultParams);
        return "{$this->baseUrl}?{$queryString}";
    }

    protected function extractArticles(array $response): array
    {
        return $response['response']['results'] ?? [];
    }

    public function normalizeArticle(array $article): array
    {
        $fields = $article['fields'] ?? [];
        
        if (empty($article['webTitle']) || empty($article['webUrl'])) {
            return [];
        }

        return [
            'title' => $article['webTitle'],
            'content' => $fields['bodyText'] ?? $fields['trailText'] ?? $fields['standfirst'] ?? '',
            'author' => $fields['byline'] ?? 'The Guardian',
            'source' => 'The Guardian',
            'source_id' => 'guardian_' . ($article['id'] ?? md5($article['webUrl'])),
            'category' => $this->normalizeCategory($article['sectionName'] ?? 'general'),
            'published_at' => Carbon::parse($article['webPublicationDate'])->toDateTimeString(),
            'url' => $article['webUrl'],
            'image_url' => $fields['thumbnail'] ?? null,
        ];
    }

    private function normalizeCategory(string $category): string
    {
        $categoryMap = [
            'Technology' => 'technology',
            'Business' => 'business',
            'Sport' => 'sports',
            'Football' => 'sports',
            'Culture' => 'entertainment',
            'Film' => 'entertainment',
            'Music' => 'entertainment',
            'Science' => 'science',
            'Environment' => 'science',
            'World news' => 'world',
            'UK news' => 'world',
        ];

        return $categoryMap[$category] ?? strtolower($category);
    }
}
