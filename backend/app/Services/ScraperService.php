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
                        'Referer' => 'https://www.google.com/',
                        'Cache-Control' => 'max-age=0'
                    ],
                    'timeout' => 15,
                    'connect_timeout' => 10
                ];

                if ($proxy) {
                    $options['proxy'] = $proxy;
                    Log::info("Attempting with proxy: " . $proxy);
                }

                $response = $this->client->get($url, $options);
                $html = (string) $response->getBody();

                $crawler = new Crawler($html);

                $title = $crawler->filter('#productTitle')->count() > 0
                    ? trim($crawler->filter('#productTitle')->text())
                    : $crawler->filter('title')->text();

                $price = '';
                if ($crawler->filter('.a-price .a-offscreen')->count() > 0) {
                    $priceText = trim($crawler->filter('.a-price .a-offscreen')->text());
                } elseif ($crawler->filter('.priceToPay .a-offscreen')->count() > 0) {
                    $priceText = trim($crawler->filter('.priceToPay .a-offscreen')->text());
                } elseif ($crawler->filter('#priceblock_ourprice')->count() > 0) {
                    $priceText = trim($crawler->filter('#priceblock_ourprice')->text());
                } else {
                    $priceText = '';
                }

                // Extract only the numeric value from the price string
                $price = preg_replace('/[^0-9.]/', '', $priceText);

                $imageUrl = '';
                if ($crawler->filter('#landingImage')->count() > 0) {
                    $imageUrl = $crawler->filter('#landingImage')->attr('src');
                }

                $existingProduct = Product::where('title', $title)->first();
                if ($existingProduct) {
                    Log::info("Product already exists: {$title}");
                    return true;
                }

                $product = new Product();
                $product->title = $title;
                $product->price = $price;
                $product->image_url = $imageUrl;
                $product->save();

                return true;
            } catch (GuzzleException $e) {
                $attempt++;
                Log::warning("Scraping attempt $attempt failed: " . $e->getMessage());

                if ($attempt >= $maxRetries) {
                    Log::error('Scraping error after ' . $maxRetries . ' attempts: ' . $e->getMessage());
                    return false;
                }

                sleep(2);
            } catch (Exception $e) {
                Log::error('Processing error: ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }
}
