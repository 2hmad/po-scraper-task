<?php

namespace App\Console\Commands;

use App\Services\ScraperService;
use Illuminate\Console\Command;

class ScrapeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $scraperService;
    private $defaultUrls = [
        'https://www.amazon.com/dp/B07VGRJDFY',
        'https://www.amazon.com/dp/B07QD6R5L7',
        'https://www.amazon.com/dp/B01N1037CV',
    ];

    /**
     * Create a new command instance.
     *
     * @param ScraperService $scraperService
     */
    public function __construct(ScraperService $scraperService)
    {
        parent::__construct();
        $this->scraperService = $scraperService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $successCount = 0;
        foreach ($this->defaultUrls as $url) {
            $this->info("Scraping: {$url}");
            $result = $this->scraperService->scrapeProductFromUrl($url);
            if ($result) {
                $successCount++;
            }
        }

        $this->info("Completed! Successfully scraped {$successCount} out of " . count($this->defaultUrls) . " products.");
    }
}
