<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService
{
    private $client;
    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.81 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.97 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'verify' => false,
        ]);
    }

    /**
     * Get a random user agent from the list
     * @return string
     */
    private function getRandomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    /**
     * Get proxy from the Go microservice
     * @return string|null
     */
    private function getProxy(): ?string
    {
        try {
            $proxyServiceUrl = config('services.proxy.url');
            $response = $this->client->get("{$proxyServiceUrl}/proxy");
            $data = json_decode($response->getBody(), true);
            if (!empty($data['proxy'])) {
                return 'http://' . trim($data['proxy']);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get proxy: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Scrape product data from the given URL
     * @param string $url
     * @return bool
     */
    public function scrapeProductFromUrl(string $url): bool
    {
        // Increase retry count for more reliability
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $proxy = $this->getProxy();
                $options = [
                    'headers' => [
                        'User-Agent' => $this->getRandomUserAgent(),
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'en-US,en;q=0.5',
                        'Referer' => 'https://www.google.com/',  // Add referer to seem more legitimate
                        'Cache-Control' => 'max-age=0'
                    ],
                    'timeout' => 15,  // Increase timeout for slow proxies
                    'connect_timeout' => 10
                ];

                if ($proxy) {
                    $options['proxy'] = $proxy;
                    Log::info("Attempting with proxy: " . $proxy);
                }

                $response = $this->client->get($url, $options);
                $html = (string) $response->getBody();

                // Rest of your scraping code...

                return true;
            } catch (GuzzleException $e) {
                $attempt++;
                Log::warning("Scraping attempt $attempt failed: " . $e->getMessage());

                // Only log as error on the final attempt
                if ($attempt >= $maxRetries) {
                    Log::error('Scraping error after ' . $maxRetries . ' attempts: ' . $e->getMessage());
                    return false;
                }

                // Wait before retrying
                sleep(2);
            } catch (Exception $e) {
                Log::error('Processing error: ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }
}
