<?php
namespace App\Contracts;

interface NewsSourceInterface
{
    /**
     * Fetch articles from the news source
     *
     * @param array $params Additional parameters for filtering
     * @return array Array of raw article data
     */
    public function fetchArticles(array $params = []): array;

    /**
     * Normalize article data to match our database schema
     *
     * @param array $article Raw article data from API
     * @return array Normalized article data
     */
    public function normalizeArticle(array $article): array;

    /**
     * Get the source name
     *
     * @return string
     */
    public function getSourceName(): string;
}
