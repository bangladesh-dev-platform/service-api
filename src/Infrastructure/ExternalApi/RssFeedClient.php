<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

/**
 * RSS/Atom Feed Client for fetching news from various sources
 */
class RssFeedClient
{
    private const USER_AGENT = 'banglade.sh News Aggregator/1.0';
    private const TIMEOUT = 10;

    /**
     * News sources configuration
     * Each source has: url, type (rss|atom), name, name_bn, category mappings
     */
    private const SOURCES = [
        'prothomalo' => [
            'url' => 'https://www.prothomalo.com/feed',
            'type' => 'atom',
            'name' => 'Prothom Alo',
            'name_bn' => 'প্রথম আলো',
            'logo' => 'https://www.prothomalo.com/static/starter-starter/media/common/prothomalo/logo.svg',
            'category_map' => [
                'bangladesh' => 'national',
                'politics' => 'national',
                'international' => 'international',
                'sports' => 'sports',
                'business' => 'business',
                'technology' => 'technology',
                'entertainment' => 'entertainment',
            ],
        ],
        'kalerkantho' => [
            'url' => 'https://www.kalerkantho.com/rss.xml',
            'type' => 'rss',
            'name' => 'Kaler Kantho',
            'name_bn' => 'কালের কণ্ঠ',
            'logo' => 'https://www.kalerkantho.com/assets/frontend/images/kaler-kantho-logo.png',
            'category_map' => [
                'national' => 'national',
                'country-news' => 'national',
                'Politics' => 'national',
                'international' => 'international',
                'sports' => 'sports',
                'business' => 'business',
                'technology' => 'technology',
                'entertainment' => 'entertainment',
            ],
        ],
    ];

    /**
     * Fetch news from all configured sources
     * Uses round-robin mixing to ensure source diversity
     */
    public function fetchAllNews(int $limit = 20): array
    {
        $sourceItems = [];
        $sourceCount = count(self::SOURCES);
        $perSourceFetch = max(30, (int)ceil($limit * 1.5 / $sourceCount));

        // Fetch and sort items from each source
        foreach (self::SOURCES as $sourceKey => $config) {
            try {
                $items = $this->fetchFromSource($sourceKey, $config);
                // Sort each source's items by date
                usort($items, fn($a, $b) => strtotime($b['published_at']) - strtotime($a['published_at']));
                $sourceItems[$sourceKey] = array_slice($items, 0, $perSourceFetch);
            } catch (\Throwable $e) {
                error_log("RSS fetch error for {$sourceKey}: " . $e->getMessage());
                $sourceItems[$sourceKey] = [];
            }
        }

        // Round-robin mix to ensure diversity (alternating between sources)
        // This ensures we get items from all sources even if one has more recent news
        $result = [];
        $maxRounds = max(array_map('count', $sourceItems) ?: [0]);
        
        for ($i = 0; $i < $maxRounds && count($result) < $limit; $i++) {
            foreach ($sourceItems as $items) {
                if (isset($items[$i]) && count($result) < $limit) {
                    $result[] = $items[$i];
                }
            }
        }

        // Don't sort after mixing - preserve the round-robin interleaving
        // Items from each source are already sorted by date

        return array_slice($result, 0, $limit);
    }

    /**
     * Fetch news from a specific source
     */
    public function fetchFromSource(string $sourceKey, ?array $config = null): array
    {
        $config = $config ?? self::SOURCES[$sourceKey] ?? null;
        if (!$config) {
            throw new \InvalidArgumentException("Unknown source: {$sourceKey}");
        }

        $xml = $this->fetchXml($config['url']);
        
        if ($config['type'] === 'atom') {
            return $this->parseAtom($xml, $config);
        }
        
        return $this->parseRss($xml, $config);
    }

    /**
     * Fetch XML content from URL
     */
    private function fetchXml(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/rss+xml, application/atom+xml, application/xml, text/xml',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            throw new \RuntimeException("Failed to fetch RSS: {$error} (HTTP {$httpCode})");
        }

        return $response;
    }

    /**
     * Parse RSS 2.0 format
     */
    private function parseRss(string $xml, array $config): array
    {
        $doc = new \SimpleXMLElement($xml);
        $items = [];

        foreach ($doc->channel->item as $item) {
            $category = $this->extractCategory((string)$item->link, $config);
            
            $items[] = [
                'id' => md5((string)$item->guid ?: (string)$item->link),
                'title' => trim((string)$item->title),
                'title_en' => null, // Bangla source
                'summary' => $this->cleanSummary((string)$item->description),
                'summary_en' => null,
                'url' => (string)$item->link,
                'image' => $this->extractImage($item),
                'source' => $config['name_bn'],
                'source_en' => $config['name'],
                'source_key' => array_search($config, self::SOURCES) ?: 'unknown',
                'source_logo' => $config['logo'] ?? null,
                'category' => $category,
                'published_at' => $this->parseDateTime((string)$item->pubDate, $config),
            ];
        }

        return $items;
    }

    /**
     * Parse Atom format
     */
    private function parseAtom(string $xml, array $config): array
    {
        // Register Atom namespace
        $doc = new \SimpleXMLElement($xml);
        $doc->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        
        $items = [];
        
        // Get entries - try both with and without namespace
        $entries = $doc->xpath('//atom:entry') ?: $doc->entry;
        
        foreach ($entries as $entry) {
            // Get link href
            $link = '';
            $links = $entry->link;
            foreach ($links as $linkEl) {
                $rel = (string)$linkEl['rel'];
                if ($rel === 'alternate' || $rel === '') {
                    $link = (string)$linkEl['href'];
                    break;
                }
            }

            // Get category
            $categoryTerm = '';
            if (isset($entry->category)) {
                $categoryTerm = (string)$entry->category['term'];
            }
            $category = $this->mapCategory($categoryTerm, $config);

            $items[] = [
                'id' => md5((string)$entry->id),
                'title' => trim((string)$entry->title),
                'title_en' => null,
                'summary' => $this->cleanSummary((string)$entry->summary),
                'summary_en' => null,
                'url' => $link,
                'image' => $this->extractImageFromContent($entry),
                'source' => $config['name_bn'],
                'source_en' => $config['name'],
                'source_key' => array_search($config, self::SOURCES) ?: 'unknown',
                'source_logo' => $config['logo'] ?? null,
                'category' => $category,
                'published_at' => date('c', strtotime((string)$entry->published ?: (string)$entry->updated)),
            ];
        }

        return $items;
    }

    /**
     * Extract category from URL path
     */
    private function extractCategory(string $url, array $config): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segments = explode('/', trim($path, '/'));
        
        // Try to find category in URL segments
        foreach ($segments as $segment) {
            $mapped = $this->mapCategory($segment, $config);
            if ($mapped !== 'national') { // 'national' is default
                return $mapped;
            }
        }
        
        return 'national';
    }

    /**
     * Map source category to standard category
     */
    private function mapCategory(string $sourceCategory, array $config): string
    {
        $map = $config['category_map'] ?? [];
        $lower = strtolower($sourceCategory);
        
        return $map[$sourceCategory] ?? $map[$lower] ?? 'national';
    }

    /**
     * Extract image from RSS item
     */
    private function extractImage(\SimpleXMLElement $item): ?string
    {
        // Try media:content namespace
        $namespaces = $item->getNamespaces(true);
        if (isset($namespaces['media'])) {
            $media = $item->children($namespaces['media']);
            if (isset($media->content)) {
                return (string)$media->content['url'];
            }
            if (isset($media->thumbnail)) {
                return (string)$media->thumbnail['url'];
            }
        }

        // Try enclosure
        if (isset($item->enclosure) && (string)$item->enclosure['type'] === 'image/jpeg') {
            return (string)$item->enclosure['url'];
        }

        // Try to extract from description HTML
        $desc = (string)$item->description;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $desc, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract image from Atom content
     */
    private function extractImageFromContent(\SimpleXMLElement $entry): ?string
    {
        // Try media namespace
        $namespaces = $entry->getNamespaces(true);
        if (isset($namespaces['media'])) {
            $media = $entry->children($namespaces['media']);
            if (isset($media->content)) {
                return (string)$media->content['url'];
            }
        }

        // Try content HTML
        if (isset($entry->content)) {
            $content = (string)$entry->content;
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Parse datetime string with source-specific timezone corrections
     * 
     * Kaler Kantho RSS claims to be in GMT but is actually Bangladesh time (UTC+6).
     * We correct for this by subtracting 6 hours from their timestamps.
     */
    private function parseDateTime(string $dateStr, array $config): string
    {
        $timestamp = strtotime($dateStr);
        
        if ($timestamp === false) {
            return date('c'); // Fallback to current time
        }
        
        // Get the source key
        $sourceKey = array_search($config, self::SOURCES, true);
        
        // Kaler Kantho bug: They say "GMT" but times are actually Bangladesh time (UTC+6)
        // So we need to subtract 6 hours to get the actual UTC time
        if ($sourceKey === 'kalerkantho') {
            $timestamp -= (6 * 60 * 60); // Subtract 6 hours
        }
        
        return date('c', $timestamp);
    }

    /**
     * Clean summary text
     */
    private function cleanSummary(?string $text): ?string
    {
        if (!$text) return null;
        
        // Strip HTML tags
        $text = strip_tags($text);
        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        // Trim and limit length
        $text = trim($text);
        
        if (mb_strlen($text) > 300) {
            $text = mb_substr($text, 0, 297) . '...';
        }
        
        return $text ?: null;
    }

    /**
     * Get available sources
     */
    public function getSources(): array
    {
        $sources = [];
        foreach (self::SOURCES as $key => $config) {
            $sources[] = [
                'key' => $key,
                'name' => $config['name'],
                'name_bn' => $config['name_bn'],
                'logo' => $config['logo'] ?? null,
            ];
        }
        return $sources;
    }
}
