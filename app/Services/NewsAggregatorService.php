<?php

namespace App\Services;

use App\Models\Article;
use App\Services\NewsAggregator\NewsApiSource;
use App\Services\NewsAggregator\GuardianSource;
use App\Services\NewsAggregator\BbcSource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NewsAggregatorService
{
    protected array $sources;

    public function __construct()
    {
        $this->sources = [
            new NewsApiSource(),
            new GuardianSource(),
            new BbcSource(),
        ];
    }

    /**
     * Aggregate news from all sources
     *
     * @return array Statistics about the aggregation
     */
    public function aggregateNews(): array
    {
        $stats = [
            'total' => 0,
            'new' => 0,
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'sources' => [],
        ];

        foreach ($this->sources as $source) {
            $sourceName = $source->getSourceName();
            $sourceStats = [
                'name' => $sourceName,
                'articles' => 0,
                'new' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];

            try {
                Log::info("Starting aggregation from {$sourceName}");
                
                $articles = $source->fetchArticles();
                
                foreach ($articles as $articleData) {
                    try {
                        $normalized = $source->normalizeArticle($articleData);
                        
                        // Skip invalid articles
                        if (empty($normalized) || empty($normalized['title'])) {
                            $sourceStats['skipped']++;
                            continue;
                        }

                        DB::beginTransaction();
                        
                        $article = Article::updateOrCreate(
                            ['source_id' => $normalized['source_id']],
                            $normalized
                        );

                        DB::commit();

                        if ($article->wasRecentlyCreated) {
                            $sourceStats['new']++;
                            $stats['new']++;
                        } else {
                            $sourceStats['updated']++;
                            $stats['updated']++;
                        }
                        
                        $sourceStats['articles']++;
                        $stats['total']++;

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::warning("Failed to save article from {$sourceName}", [
                            'error' => $e->getMessage(),
                            'article' => $articleData['title'] ?? 'unknown'
                        ]);
                        $sourceStats['skipped']++;
                    }
                }

                Log::info("Successfully aggregated from {$sourceName}", $sourceStats);
                $stats['sources'][] = $sourceStats;

            } catch (\Exception $e) {
                $stats['failed']++;
                Log::error("Failed to aggregate from {$sourceName}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $sourceStats['error'] = $e->getMessage();
                $stats['sources'][] = $sourceStats;
            }
        }

        $stats['skipped'] = array_sum(array_column($stats['sources'], 'skipped'));

        return $stats;
    }

    /**
     * Aggregate news from a specific source
     *
     * @param string $sourceName
     * @return array
     */
    public function aggregateFromSource(string $sourceName): array
    {
        foreach ($this->sources as $source) {
            if ($source->getSourceName() === $sourceName) {
                $articles = $source->fetchArticles();
                $stats = [
                    'source' => $sourceName,
                    'fetched' => count($articles),
                    'saved' => 0,
                ];

                foreach ($articles as $articleData) {
                    $normalized = $source->normalizeArticle($articleData);
                    if (!empty($normalized)) {
                        Article::updateOrCreate(
                            ['source_id' => $normalized['source_id']],
                            $normalized
                        );
                        $stats['saved']++;
                    }
                }

                return $stats;
            }
        }

        throw new \InvalidArgumentException("Source {$sourceName} not found");
    }

    /**
     * Get list of available sources
     *
     * @return array
     */
    public function getAvailableSources(): array
    {
        return array_map(fn($source) => $source->getSourceName(), $this->sources);
    }
}