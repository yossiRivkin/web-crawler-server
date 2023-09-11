<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Crawler as CrawlerModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class Crawler extends Controller
{
    public function store(Request $request)
    {
        // Initialize variables
        $url = $request->input('url');
        $depthLimit = $request->input('depth', 1);
        $seedUrl = $url;

        try {
            // Start crawling from the seed URL
            $this->crawlRecursive($url, 0, $depthLimit, $seedUrl);
        } catch (\Throwable $th) {
            Log::error('An error occurred: ' . $th);
        }
        // Fetch records using the CrawlerModel's method
        $results = CrawlerModel::fetchByParent($url);

        // Return the results as a JSON response
        return response()->json(['results' => $results], Response::HTTP_OK);
    }

    private function crawlRecursive($url, $currentDepth, $depthLimit, $seedUrl)
    {
        if ($currentDepth >= $depthLimit) {
            return;
        }

        // Measure the time it takes to fetch the page
        $pageContent = null;
        $options = [
            'http' => [
                'follow_location' => 1,
                'max_redirects' => 5,  // Specify the maximum number of redirects to follow
            ],
        ];
        $context = stream_context_create($options);

        $start_time = microtime(true);

        try {
            // Fetch the page content using file_get_contents
            $pageContent = file_get_contents($url, false, $context);
        } catch (\Throwable $th) {
            Log::error('An error occurred: ' . $th);
        }

        $end_time = microtime(true);
        $fetch_time = ($end_time - $start_time) * 1000; // Convert to milliseconds

        if (!$pageContent) {
            return;
        }

        // Check if we've reached the depth limit
        if ($currentDepth > 0) {
            CrawlerMODEL::storeOrUpdate(['url' => $url, 'parent' => $seedUrl]);
            Log::info('storeOrUpdate url: ' . $url);

        }

        // Parse the content for more URLs and crawl them
        $parsedUrls = $this->parseUrlsFromContent($pageContent);
        Log::info('urls to fetch: ' . print_r($parsedUrls, true));

        foreach ($parsedUrls as $parsedUrl) {
            $this->crawlRecursive($parsedUrl, $currentDepth + 1, $depthLimit, $seedUrl);
        }
    }

    private function parseUrlsFromContent($content)
    {
        $urls = [];

        // Create a DOMDocument instance
        $dom = new \DOMDocument();

        // Load the HTML content (suppressing warnings)
        @$dom->loadHTML($content);

        // Find all anchor tags (links)
        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            // Get the href attribute from each link
            $url = $link->getAttribute('href');

            // Filter out empty and non-HTTP URLs (e.g., javascript:void(0), #section)
            if (!empty($url) && strpos($url, 'http') === 0) {
                // Ensure URLs are unique before adding them
                if (!in_array($url, $urls)) {
                    $urls[] = $url;
                }
            }
        }

        return $urls;
    }


    public function fetchByParent(Request $request)
    {
        // Retrieve the 'url' query parameter from the request
        $url = $request->query('url');
        // Fetch records using the CrawlerModel's method
        $results = CrawlerModel::fetchByParent($url);

        // Return the results as a JSON response
        return response()->json($results, Response::HTTP_OK);
    }

    public function getAllUniqueParents()
    {
        // Fetch all unique parent values from the Crawler model
        $uniqueParents = CrawlerModel::getAllUniqueParents();

        // Return the unique parent values as a JSON response
        return response()->json($uniqueParents, Response::HTTP_OK);
    }
}
