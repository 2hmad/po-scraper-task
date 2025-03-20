<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ScraperService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private $scraperService;

    public function __construct(ScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    /**
     * Get all products
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $products = Product::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Trigger scraping manually
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function scrape(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        $result = $this->scraperService->scrapeProductFromUrl($request->url);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Product scraped successfully' : 'Failed to scrape product'
        ]);
    }
}
