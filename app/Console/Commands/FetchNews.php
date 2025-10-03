<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NewsAggregatorService;

class FetchNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:news';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(NewsAggregatorService $aggregator): int
    {
        $this->info('Starting news aggregation...');
        
        try {
            // The service is automatically resolved by Laravel's IoC container via Dependency Injection
            $stats = $aggregator->aggregateNews();
            
            $this->info("\nNews aggregation completed successfully!");
            $this->table(
                ['Statistic', 'Value'],
                [
                    ['Total articles processed', $stats['total'] ?? 0],
                    ['New articles saved', $stats['new'] ?? 0],
                    ['Articles updated', $stats['updated'] ?? 0],
                    ['Sources that failed', $stats['failed'] ?? 0],
                ]
            );
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Log the error and output a message to the console
            $this->error("An error occurred during news aggregation: " . $e->getMessage());
            
            // Optional: You could log the full exception stack trace
            // \Log::error("News Aggregation Failed", ['exception' => $e]);
            
            return Command::FAILURE;
        }
    }
}
