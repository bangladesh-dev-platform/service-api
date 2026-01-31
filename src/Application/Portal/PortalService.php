<?php

declare(strict_types=1);

namespace App\Application\Portal;

use App\Infrastructure\ExternalApi\OpenMeteoClient;
use App\Infrastructure\ExternalApi\ExchangeRateClient;
use App\Infrastructure\ExternalApi\RssFeedClient;
use App\Infrastructure\ExternalApi\OpenAIClient;
use App\Infrastructure\Data\BangladeshDistricts;
use App\Infrastructure\Cache\FileCache;

/**
 * Portal Service - handles all homepage data endpoints
 * Uses caching to reduce external API calls
 */
class PortalService
{
    private OpenMeteoClient $weatherClient;
    private ExchangeRateClient $exchangeClient;
    private RssFeedClient $rssClient;
    private FileCache $cache;

    // Cache TTLs (in seconds)
    private const WEATHER_CACHE_TTL = 600;      // 10 minutes
    private const CURRENCY_CACHE_TTL = 3600;    // 1 hour
    private const DIVISIONS_WEATHER_TTL = 900;  // 15 minutes
    private const NEWS_CACHE_TTL = 300;         // 5 minutes

    public function __construct(
        OpenMeteoClient $weatherClient,
        ExchangeRateClient $exchangeClient,
        ?RssFeedClient $rssClient = null,
        ?FileCache $cache = null
    ) {
        $this->weatherClient = $weatherClient;
        $this->exchangeClient = $exchangeClient;
        $this->rssClient = $rssClient ?? new RssFeedClient();
        $this->cache = $cache ?? new FileCache();
    }

    /**
     * Get weather data (cached)
     */
    public function getWeather(?float $lat = null, ?float $lon = null, ?string $districtId = null): array
    {
        // Build cache key based on location
        $lat = $lat ?? 23.8103;
        $lon = $lon ?? 90.4125;
        $cacheKey = $districtId 
            ? "weather_district_{$districtId}"
            : "weather_{$lat}_{$lon}";

        return $this->cache->remember(
            $cacheKey,
            fn() => $this->weatherClient->getWeather($lat, $lon, $districtId),
            self::WEATHER_CACHE_TTL
        );
    }

    /**
     * Get all districts grouped by division
     */
    public function getDistricts(): array
    {
        return [
            'divisions' => BangladeshDistricts::getAll(),
            'total_districts' => count(BangladeshDistricts::getAllFlat()),
        ];
    }

    /**
     * Get flat list of districts for dropdown
     */
    public function getDistrictsList(): array
    {
        return [
            'districts' => BangladeshDistricts::getAllFlat(),
        ];
    }

    /**
     * Get 8 division capitals + Cumilla for homepage dropdown
     */
    public function getWeatherLocations(): array
    {
        return [
            ['id' => 'dhaka', 'name' => 'Dhaka', 'name_bn' => 'ঢাকা', 'lat' => 23.8103, 'lon' => 90.4125, 'type' => 'division'],
            ['id' => 'chattogram', 'name' => 'Chattogram', 'name_bn' => 'চট্টগ্রাম', 'lat' => 22.3569, 'lon' => 91.7832, 'type' => 'division'],
            ['id' => 'rajshahi', 'name' => 'Rajshahi', 'name_bn' => 'রাজশাহী', 'lat' => 24.3745, 'lon' => 88.6042, 'type' => 'division'],
            ['id' => 'khulna', 'name' => 'Khulna', 'name_bn' => 'খুলনা', 'lat' => 22.8456, 'lon' => 89.5403, 'type' => 'division'],
            ['id' => 'barishal', 'name' => 'Barishal', 'name_bn' => 'বরিশাল', 'lat' => 22.7010, 'lon' => 90.3535, 'type' => 'division'],
            ['id' => 'sylhet', 'name' => 'Sylhet', 'name_bn' => 'সিলেট', 'lat' => 24.8949, 'lon' => 91.8687, 'type' => 'division'],
            ['id' => 'rangpur', 'name' => 'Rangpur', 'name_bn' => 'রংপুর', 'lat' => 25.7439, 'lon' => 89.2752, 'type' => 'division'],
            ['id' => 'mymensingh', 'name' => 'Mymensingh', 'name_bn' => 'ময়মনসিংহ', 'lat' => 24.7471, 'lon' => 90.4203, 'type' => 'division'],
            ['id' => 'comilla', 'name' => 'Comilla', 'name_bn' => 'কুমিল্লা', 'lat' => 23.4607, 'lon' => 91.1809, 'type' => 'district'],
        ];
    }

    /**
     * Get weather for multiple districts (for weather page) - cached
     */
    public function getWeatherBulk(?string $divisionId = null): array
    {
        $cacheKey = "weather_bulk_" . ($divisionId ?? 'all');
        
        return $this->cache->remember($cacheKey, function() use ($divisionId) {
            $districts = BangladeshDistricts::getAllFlat();
            
            // Filter by division if specified
            if ($divisionId) {
                $districts = array_filter($districts, fn($d) => strtolower($d['division']) === strtolower($divisionId));
                $districts = array_values($districts);
            }

            $results = [];
            foreach ($districts as $district) {
                try {
                    $weather = $this->weatherClient->getWeather($district['lat'], $district['lon']);
                    $results[] = [
                        'district' => $district,
                        'weather' => $weather['current'],
                    ];
                } catch (\Throwable $e) {
                    // Skip failed districts
                    $results[] = [
                        'district' => $district,
                        'weather' => null,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'items' => $results,
                'total' => count($results),
                'updated_at' => date('c'),
            ];
        }, self::WEATHER_CACHE_TTL);
    }

    /**
     * Get weather for division capitals only (faster for overview) - cached
     */
    public function getWeatherDivisions(): array
    {
        return $this->cache->remember('weather_divisions', function() {
            $locations = $this->getWeatherLocations();
            $results = [];

            foreach ($locations as $location) {
                try {
                    $weather = $this->weatherClient->getWeather($location['lat'], $location['lon']);
                    $results[] = [
                        'location' => $location,
                        'current' => $weather['current'],
                        'forecast' => $weather['forecast'],
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'location' => $location,
                        'current' => null,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'items' => $results,
                'updated_at' => date('c'),
            ];
        }, self::DIVISIONS_WEATHER_TTL);
    }

    /**
     * Get currency exchange rates - cached
     */
    public function getCurrency(): array
    {
        return $this->cache->remember(
            'currency_rates',
            fn() => $this->exchangeClient->getRates(),
            self::CURRENCY_CACHE_TTL
        );
    }

    /**
     * Get news feed from real RSS sources (cached)
     * Cache stores max items, limit is applied at the end
     */
    public function getNews(?string $category = null, ?string $source = null, int $limit = 10): array
    {
        // Cache key includes source and category, but not limit (we cache max and slice)
        $cacheKey = "news_" . ($source ?? 'all') . "_" . ($category ?? 'all');
        
        $cached = $this->cache->remember($cacheKey, function() use ($category, $source) {
            try {
                // Fetch from RSS feeds - get max items for caching
                $allNews = $this->rssClient->fetchAllNews(100);
                
                // Filter by source if specified
                if ($source && $source !== 'all') {
                    $allNews = array_filter($allNews, fn($item) => $item['source_key'] === $source);
                    $allNews = array_values($allNews);
                }
                
                // Filter by category if specified
                if ($category && $category !== 'all') {
                    $allNews = array_filter($allNews, fn($item) => $item['category'] === $category);
                    $allNews = array_values($allNews);
                }
                
                return [
                    'items' => $allNews, // Store all filtered items
                    'sources' => $this->rssClient->getSources(),
                    'categories' => ['all', 'national', 'international', 'sports', 'business', 'technology', 'entertainment'],
                    'updated_at' => date('c'),
                    'is_live' => true,
                ];
            } catch (\Throwable $e) {
                // Fallback to mock data if RSS fails
                error_log("News RSS error: " . $e->getMessage());
                return $this->getMockNewsResponse($category, 50);
            }
        }, self::NEWS_CACHE_TTL);

        // Interleave items from different sources for diversity, then apply limit
        $items = $cached['items'];
        
        // Group by source
        $bySource = [];
        foreach ($items as $item) {
            $key = $item['source_key'] ?? 'unknown';
            if (!isset($bySource[$key])) {
                $bySource[$key] = [];
            }
            $bySource[$key][] = $item;
        }
        
        // Round-robin interleave
        $interleaved = [];
        $maxCount = max(array_map('count', $bySource) ?: [0]);
        for ($i = 0; $i < $maxCount && count($interleaved) < $limit; $i++) {
            foreach ($bySource as $sourceItems) {
                if (isset($sourceItems[$i]) && count($interleaved) < $limit) {
                    $interleaved[] = $sourceItems[$i];
                }
            }
        }
        
        $cached['items'] = $interleaved;
        return $cached;
    }

    /**
     * Fallback mock news response
     */
    private function getMockNewsResponse(?string $category, int $limit): array
    {
        $allNews = $this->getMockNews();
        
        if ($category && $category !== 'all') {
            $allNews = array_filter($allNews, fn($item) => $item['category'] === $category);
        }
        
        return [
            'items' => array_slice(array_values($allNews), 0, $limit),
            'sources' => [],
            'categories' => ['all', 'national', 'international', 'sports', 'business', 'technology'],
            'updated_at' => date('c'),
            'is_live' => false,
        ];
    }

    /**
     * Get radio stations with real streaming URLs
     * Sources: Zeno.FM, official radio websites
     */
    public function getRadioStations(): array
    {
        return [
            'stations' => [
                [
                    'id' => 1,
                    'name' => 'রেডিও টুডে',
                    'name_en' => 'Radio Today',
                    'frequency' => '89.6 FM',
                    'genre' => 'সংবাদ ও বিনোদন',
                    'genre_en' => 'News & Entertainment',
                    'stream_url' => 'https://stream.zeno.fm/0zha3rfq02quv',
                    'logo' => 'https://cdn-profiles.tunein.com/s122538/images/logod.png',
                    'website' => 'https://radiotoday.fm',
                ],
                [
                    'id' => 2,
                    'name' => 'রেডিও ফুর্তি',
                    'name_en' => 'Radio Foorti',
                    'frequency' => '88.0 FM',
                    'genre' => 'সঙ্গীত',
                    'genre_en' => 'Music & Entertainment',
                    'stream_url' => 'https://stream.zeno.fm/kdb2ntppchzuv',
                    'logo' => 'https://cdn-profiles.tunein.com/s122537/images/logod.png',
                    'website' => 'https://radiofoorti.fm',
                ],
                [
                    'id' => 3,
                    'name' => 'এবিসি রেডিও',
                    'name_en' => 'ABC Radio',
                    'frequency' => '89.2 FM',
                    'genre' => 'সংবাদ',
                    'genre_en' => 'News & Talk',
                    'stream_url' => 'https://stream.zeno.fm/pyr2f3wmfzzuv',
                    'logo' => 'https://cdn-profiles.tunein.com/s122535/images/logod.png',
                    'website' => 'https://abcradio.fm',
                ],
                [
                    'id' => 4,
                    'name' => 'রেডিও ভূমি',
                    'name_en' => 'Radio Bhumi',
                    'frequency' => '92.8 FM',
                    'genre' => 'সঙ্গীত',
                    'genre_en' => 'Music',
                    'stream_url' => 'https://stream.zeno.fm/d195hkp3ra0uv',
                    'logo' => 'https://cdn-profiles.tunein.com/s225829/images/logod.png',
                    'website' => 'https://radiobhumi.fm',
                ],
                [
                    'id' => 5,
                    'name' => 'বাংলাদেশ বেতার',
                    'name_en' => 'Bangladesh Betar',
                    'frequency' => '100.0 FM',
                    'genre' => 'সরকারি রেডিও',
                    'genre_en' => 'Public Radio',
                    'stream_url' => 'https://stream.zeno.fm/xghhkq8s7p8uv',
                    'logo' => 'https://cdn-profiles.tunein.com/s10705/images/logod.png',
                    'website' => 'https://betar.gov.bd',
                ],
                [
                    'id' => 6,
                    'name' => 'পিপলস রেডিও',
                    'name_en' => 'Peoples Radio',
                    'frequency' => '91.6 FM',
                    'genre' => 'সঙ্গীত ও বিনোদন',
                    'genre_en' => 'Music & Entertainment',
                    'stream_url' => 'https://stream.zeno.fm/8qknmzpb5qhvv',
                    'logo' => 'https://cdn-profiles.tunein.com/s122542/images/logod.png',
                    'website' => 'https://peoplesradio.fm',
                ],
                [
                    'id' => 7,
                    'name' => 'রেডিও শাধীন',
                    'name_en' => 'Radio Shadhin',
                    'frequency' => '92.4 FM',
                    'genre' => 'যুব বিনোদন',
                    'genre_en' => 'Youth & Entertainment',
                    'stream_url' => 'https://stream.zeno.fm/s7cpk8h1068uv',
                    'logo' => 'https://cdn-profiles.tunein.com/s225843/images/logod.png',
                    'website' => 'https://radioshadhin.fm',
                ],
                [
                    'id' => 8,
                    'name' => 'স্পাইস এফএম',
                    'name_en' => 'Spice FM',
                    'frequency' => '96.4 FM',
                    'genre' => 'তরুণ সঙ্গীত',
                    'genre_en' => 'Urban Music',
                    'stream_url' => 'https://stream.zeno.fm/h2rwz4e5da0uv',
                    'logo' => 'https://cdn-profiles.tunein.com/s279610/images/logod.png',
                    'website' => 'https://spicefm.fm',
                ],
            ],
            'updated_at' => date('c'),
        ];
    }

    /**
     * Get job listings (mock data)
     */
    public function getJobs(?string $type = null, int $limit = 10): array
    {
        $jobs = $this->getMockJobs();
        
        if ($type && $type !== 'all') {
            $jobs = array_filter($jobs, fn($item) => $item['type'] === $type);
        }
        
        return [
            'items' => array_slice(array_values($jobs), 0, $limit),
            'types' => ['all', 'government', 'private', 'ngo'],
            'updated_at' => date('c'),
        ];
    }

    /**
     * Get government notices (mock data)
     */
    public function getNotices(?string $category = null, int $limit = 10): array
    {
        $notices = $this->getMockNotices();
        
        if ($category && $category !== 'all') {
            $notices = array_filter($notices, fn($item) => $item['category'] === $category);
        }
        
        return [
            'items' => array_slice(array_values($notices), 0, $limit),
            'categories' => ['all', 'jobs', 'education', 'policy', 'tender'],
            'updated_at' => date('c'),
        ];
    }

    /**
     * Get education content (mock data)
     */
    public function getEducation(?string $type = null, int $limit = 10): array
    {
        return [
            'items' => $this->getMockEducation(),
            'types' => ['tips', 'resources', 'scholarships', 'results'],
            'updated_at' => date('c'),
        ];
    }

    /**
     * Get market deals (mock data)
     */
    public function getMarketDeals(?string $category = null, int $limit = 10): array
    {
        $deals = $this->getMockDeals();
        
        if ($category && $category !== 'all') {
            $deals = array_filter($deals, fn($item) => $item['category'] === $category);
        }
        
        return [
            'items' => array_slice(array_values($deals), 0, $limit),
            'categories' => ['all', 'electronics', 'fashion', 'food', 'books', 'health'],
            'updated_at' => date('c'),
        ];
    }

    // =========== Mock Data Methods ===========

    private function getMockNews(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'বাংলাদেশের অর্থনীতিতে নতুন অগ্রগতি',
                'title_en' => 'New Progress in Bangladesh Economy',
                'summary' => 'দেশের অর্থনৈতিক প্রবৃদ্ধি ৭.৫% ছাড়িয়েছে।',
                'summary_en' => 'The country\'s economic growth has exceeded 7.5%.',
                'source' => 'প্রথম আলো',
                'source_en' => 'Prothom Alo',
                'category' => 'national',
                'image' => 'https://via.placeholder.com/300x200/3b82f6/ffffff?text=Economy',
                'url' => '#',
                'published_at' => date('c', strtotime('-2 hours')),
            ],
            [
                'id' => 2,
                'title' => 'জাতীয় ক্রিকেট দলের বিশ্বকাপ প্রস্তুতি',
                'title_en' => 'National Cricket Team World Cup Preparation',
                'summary' => 'টাইগাররা আসন্ন বিশ্বকাপের জন্য কঠোর প্রশিক্ষণে।',
                'summary_en' => 'Tigers are in rigorous training for the upcoming World Cup.',
                'source' => 'কালের কণ্ঠ',
                'source_en' => 'Kaler Kantho',
                'category' => 'sports',
                'image' => 'https://via.placeholder.com/300x200/22c55e/ffffff?text=Cricket',
                'url' => '#',
                'published_at' => date('c', strtotime('-4 hours')),
            ],
            [
                'id' => 3,
                'title' => 'নতুন প্রযুক্তি পার্ক উদ্বোধন',
                'title_en' => 'New Technology Park Inaugurated',
                'summary' => 'গাজীপুরে দেশের বৃহত্তম প্রযুক্তি পার্ক চালু হলো।',
                'summary_en' => 'The country\'s largest technology park launched in Gazipur.',
                'source' => 'ডেইলি স্টার',
                'source_en' => 'Daily Star',
                'category' => 'technology',
                'image' => 'https://via.placeholder.com/300x200/8b5cf6/ffffff?text=Tech',
                'url' => '#',
                'published_at' => date('c', strtotime('-6 hours')),
            ],
            [
                'id' => 4,
                'title' => 'শেয়ারবাজারে নতুন রেকর্ড',
                'title_en' => 'New Record in Stock Market',
                'summary' => 'ডিএসই সূচক ৮০০০ পয়েন্ট অতিক্রম করেছে।',
                'summary_en' => 'DSE index has crossed 8000 points.',
                'source' => 'বণিক বার্তা',
                'source_en' => 'Bonik Barta',
                'category' => 'business',
                'image' => 'https://via.placeholder.com/300x200/f59e0b/ffffff?text=Stock',
                'url' => '#',
                'published_at' => date('c', strtotime('-8 hours')),
            ],
            [
                'id' => 5,
                'title' => 'জাতিসংঘে বাংলাদেশের নতুন প্রস্তাব',
                'title_en' => 'Bangladesh\'s New Proposal at UN',
                'summary' => 'জলবায়ু পরিবর্তন মোকাবেলায় নতুন উদ্যোগ।',
                'summary_en' => 'New initiative to combat climate change.',
                'source' => 'বিডিনিউজ',
                'source_en' => 'BDNews24',
                'category' => 'international',
                'image' => 'https://via.placeholder.com/300x200/ef4444/ffffff?text=UN',
                'url' => '#',
                'published_at' => date('c', strtotime('-10 hours')),
            ],
        ];
    }

    private function getMockJobs(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'সহকারী শিক্ষক',
                'title_en' => 'Assistant Teacher',
                'organization' => 'প্রাথমিক শিক্ষা অধিদপ্তর',
                'organization_en' => 'Directorate of Primary Education',
                'location' => 'ঢাকা',
                'location_en' => 'Dhaka',
                'salary' => '৳২৫,০০০-৪০,০০০',
                'salary_en' => '৳25,000-40,000',
                'deadline' => date('Y-m-d', strtotime('+10 days')),
                'type' => 'government',
                'vacancies' => 500,
                'url' => '#',
            ],
            [
                'id' => 2,
                'title' => 'জুনিয়র অফিসার',
                'title_en' => 'Junior Officer',
                'organization' => 'বাংলাদেশ ব্যাংক',
                'organization_en' => 'Bangladesh Bank',
                'location' => 'সারাদেশ',
                'location_en' => 'Nationwide',
                'salary' => '৳৩০,০০০-৫০,০০০',
                'salary_en' => '৳30,000-50,000',
                'deadline' => date('Y-m-d', strtotime('+15 days')),
                'type' => 'government',
                'vacancies' => 200,
                'url' => '#',
            ],
            [
                'id' => 3,
                'title' => 'সফটওয়্যার ইঞ্জিনিয়ার',
                'title_en' => 'Software Engineer',
                'organization' => 'গ্রামীণফোন',
                'organization_en' => 'Grameenphone',
                'location' => 'ঢাকা',
                'location_en' => 'Dhaka',
                'salary' => '৳৮০,০০০-১,২০,০০০',
                'salary_en' => '৳80,000-120,000',
                'deadline' => date('Y-m-d', strtotime('+7 days')),
                'type' => 'private',
                'vacancies' => 10,
                'url' => '#',
            ],
            [
                'id' => 4,
                'title' => 'প্রোগ্রাম অফিসার',
                'title_en' => 'Program Officer',
                'organization' => 'ব্র্যাক',
                'organization_en' => 'BRAC',
                'location' => 'সিলেট',
                'location_en' => 'Sylhet',
                'salary' => '৳৪৫,০০০-৬০,০০০',
                'salary_en' => '৳45,000-60,000',
                'deadline' => date('Y-m-d', strtotime('+20 days')),
                'type' => 'ngo',
                'vacancies' => 5,
                'url' => '#',
            ],
        ];
    }

    private function getMockNotices(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'সরকারি চাকরির নতুন বিজ্ঞপ্তি',
                'title_en' => 'New Government Job Circular',
                'office' => 'জনপ্রশাসন মন্ত্রণালয়',
                'office_en' => 'Ministry of Public Administration',
                'category' => 'jobs',
                'date' => date('Y-m-d', strtotime('-2 days')),
                'url' => '#',
            ],
            [
                'id' => 2,
                'title' => 'শিক্ষা বৃত্তির আবেদন শুরু',
                'title_en' => 'Education Scholarship Application Started',
                'office' => 'শিক্ষা মন্ত্রণালয়',
                'office_en' => 'Ministry of Education',
                'category' => 'education',
                'date' => date('Y-m-d', strtotime('-4 days')),
                'url' => '#',
            ],
            [
                'id' => 3,
                'title' => 'নতুন ট্যাক্স পলিসি ঘোষণা',
                'title_en' => 'New Tax Policy Announced',
                'office' => 'অর্থ মন্ত্রণালয়',
                'office_en' => 'Ministry of Finance',
                'category' => 'policy',
                'date' => date('Y-m-d', strtotime('-6 days')),
                'url' => '#',
            ],
            [
                'id' => 4,
                'title' => 'সরকারি টেন্ডার বিজ্ঞপ্তি',
                'title_en' => 'Government Tender Notice',
                'office' => 'সড়ক ও জনপথ বিভাগ',
                'office_en' => 'Roads and Highways Department',
                'category' => 'tender',
                'date' => date('Y-m-d', strtotime('-1 day')),
                'url' => '#',
            ],
        ];
    }

    private function getMockEducation(): array
    {
        return [
            [
                'id' => 1,
                'type' => 'tips',
                'title' => 'এইচএসসি পরীক্ষার প্রস্তুতি',
                'title_en' => 'HSC Exam Preparation',
                'description' => 'কার্যকর অধ্যয়ন পদ্ধতি',
                'description_en' => 'Effective study methods',
                'url' => '#',
            ],
            [
                'id' => 2,
                'type' => 'resources',
                'title' => 'গণিত সমাধান গাইড',
                'title_en' => 'Math Solution Guide',
                'description' => 'ক্লাস ৯-১০ এর জন্য',
                'description_en' => 'For Class 9-10',
                'url' => '#',
            ],
            [
                'id' => 3,
                'type' => 'scholarships',
                'title' => 'মেধা বৃত্তি ২০২৫',
                'title_en' => 'Merit Scholarship 2025',
                'description' => 'আবেদনের শেষ তারিখ ৩০ জানুয়ারি',
                'description_en' => 'Application deadline January 30',
                'url' => '#',
            ],
            [
                'id' => 4,
                'type' => 'results',
                'title' => 'এসএসসি ফলাফল ২০২৫',
                'title_en' => 'SSC Results 2025',
                'description' => 'ফলাফল প্রকাশিত',
                'description_en' => 'Results published',
                'url' => '#',
            ],
        ];
    }

    private function getMockDeals(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'স্মার্টফোন - ৫০% ছাড়',
                'title_en' => 'Smartphone - 50% Off',
                'price' => 15000,
                'original_price' => 30000,
                'discount' => 50,
                'shop' => 'টেক শপ',
                'shop_en' => 'Tech Shop',
                'category' => 'electronics',
                'rating' => 4.5,
                'expires_at' => date('c', strtotime('+2 days')),
                'image' => 'https://via.placeholder.com/200x200/3b82f6/ffffff?text=Phone',
            ],
            [
                'id' => 2,
                'title' => 'পুরুষদের শার্ট - ৩০% ছাড়',
                'title_en' => "Men's Shirt - 30% Off",
                'price' => 1400,
                'original_price' => 2000,
                'discount' => 30,
                'shop' => 'ফ্যাশন হাউস',
                'shop_en' => 'Fashion House',
                'category' => 'fashion',
                'rating' => 4.2,
                'expires_at' => date('c', strtotime('+1 day')),
                'image' => 'https://via.placeholder.com/200x200/ef4444/ffffff?text=Shirt',
            ],
            [
                'id' => 3,
                'title' => 'অর্গানিক মধু - ২৫% ছাড়',
                'title_en' => 'Organic Honey - 25% Off',
                'price' => 600,
                'original_price' => 800,
                'discount' => 25,
                'shop' => 'প্রাকৃতিক খাদ্য',
                'shop_en' => 'Natural Foods',
                'category' => 'food',
                'rating' => 4.8,
                'expires_at' => date('c', strtotime('+3 days')),
                'image' => 'https://via.placeholder.com/200x200/f59e0b/ffffff?text=Honey',
            ],
            [
                'id' => 4,
                'title' => 'প্রোগ্রামিং বই সেট - ৪০% ছাড়',
                'title_en' => 'Programming Books Set - 40% Off',
                'price' => 1800,
                'original_price' => 3000,
                'discount' => 40,
                'shop' => 'বুক স্টোর',
                'shop_en' => 'Book Store',
                'category' => 'books',
                'rating' => 4.6,
                'expires_at' => date('c', strtotime('+5 days')),
                'image' => 'https://via.placeholder.com/200x200/10b981/ffffff?text=Books',
            ],
        ];
    }

    // =========== New Widget Methods ===========

    /**
     * Get prayer times for a location using Aladhan API
     * https://aladhan.com/prayer-times-api
     */
    public function getPrayerTimes(?string $city = 'Dhaka', ?string $country = 'Bangladesh'): array
    {
        $cacheKey = "prayer_times_{$city}";
        
        return $this->cache->remember($cacheKey, function() use ($city, $country) {
            try {
                $date = date('d-m-Y');
                $url = "https://api.aladhan.com/v1/timingsByCity/{$date}?city=" . urlencode($city) . "&country=" . urlencode($country) . "&method=1";
                
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);
                    if ($data['code'] === 200) {
                        $timings = $data['data']['timings'];
                        $dateInfo = $data['data']['date'];
                        
                        return [
                            'timings' => [
                                'fajr' => $timings['Fajr'],
                                'sunrise' => $timings['Sunrise'],
                                'dhuhr' => $timings['Dhuhr'],
                                'asr' => $timings['Asr'],
                                'maghrib' => $timings['Maghrib'],
                                'isha' => $timings['Isha'],
                            ],
                            'date' => [
                                'gregorian' => $dateInfo['gregorian']['date'],
                                'hijri' => $dateInfo['hijri']['date'],
                                'hijri_month' => $dateInfo['hijri']['month']['en'],
                                'hijri_year' => $dateInfo['hijri']['year'],
                            ],
                            'city' => $city,
                            'country' => $country,
                            'updated_at' => date('c'),
                            'is_live' => true,
                        ];
                    }
                }
                
                throw new \RuntimeException('Failed to fetch prayer times');
            } catch (\Throwable $e) {
                // Fallback to calculated times for Dhaka
                return $this->getFallbackPrayerTimes($city);
            }
        }, 3600); // Cache for 1 hour
    }

    /**
     * Fallback prayer times (approximate for Dhaka)
     */
    private function getFallbackPrayerTimes(string $city): array
    {
        return [
            'timings' => [
                'fajr' => '05:05',
                'sunrise' => '06:22',
                'dhuhr' => '12:10',
                'asr' => '15:45',
                'maghrib' => '17:55',
                'isha' => '19:10',
            ],
            'date' => [
                'gregorian' => date('d-m-Y'),
                'hijri' => '', // Would need calculation
                'hijri_month' => '',
                'hijri_year' => '',
            ],
            'city' => $city,
            'country' => 'Bangladesh',
            'updated_at' => date('c'),
            'is_live' => false,
        ];
    }

    /**
     * Get cricket scores from CricAPI or fallback
     */
    public function getCricketScores(): array
    {
        $cacheKey = 'cricket_scores';
        
        return $this->cache->remember($cacheKey, function() {
            // For now, return mock/scheduled matches
            // In production, integrate with CricAPI or similar
            return [
                'live' => [],
                'upcoming' => [
                    [
                        'id' => 1,
                        'team1' => ['name' => 'Bangladesh', 'name_bn' => 'বাংলাদেশ', 'code' => 'BAN', 'flag' => '🇧🇩'],
                        'team2' => ['name' => 'India', 'name_bn' => 'ভারত', 'code' => 'IND', 'flag' => '🇮🇳'],
                        'format' => 'T20I',
                        'venue' => 'Sher-e-Bangla Stadium, Dhaka',
                        'venue_bn' => 'শের-এ-বাংলা স্টেডিয়াম, ঢাকা',
                        'date' => date('Y-m-d', strtotime('+3 days')),
                        'time' => '14:00',
                        'series' => 'Asia Cup 2026',
                    ],
                    [
                        'id' => 2,
                        'team1' => ['name' => 'Bangladesh', 'name_bn' => 'বাংলাদেশ', 'code' => 'BAN', 'flag' => '🇧🇩'],
                        'team2' => ['name' => 'Pakistan', 'name_bn' => 'পাকিস্তান', 'code' => 'PAK', 'flag' => '🇵🇰'],
                        'format' => 'ODI',
                        'venue' => 'Mirpur Stadium',
                        'venue_bn' => 'মিরপুর স্টেডিয়াম',
                        'date' => date('Y-m-d', strtotime('+7 days')),
                        'time' => '13:30',
                        'series' => 'Tri-Nation Series',
                    ],
                ],
                'recent' => [
                    [
                        'id' => 3,
                        'team1' => ['name' => 'Bangladesh', 'name_bn' => 'বাংলাদেশ', 'code' => 'BAN', 'flag' => '🇧🇩', 'score' => '285/7'],
                        'team2' => ['name' => 'Sri Lanka', 'name_bn' => 'শ্রীলঙ্কা', 'code' => 'SL', 'flag' => '🇱🇰', 'score' => '245/10'],
                        'format' => 'ODI',
                        'result' => 'Bangladesh won by 40 runs',
                        'result_bn' => 'বাংলাদেশ ৪০ রানে জিতেছে',
                        'date' => date('Y-m-d', strtotime('-2 days')),
                    ],
                ],
                'updated_at' => date('c'),
            ];
        }, 300); // Cache for 5 minutes
    }

    /**
     * Get commodity prices (gold, silver, fuel)
     */
    public function getCommodityPrices(): array
    {
        $cacheKey = 'commodity_prices';
        
        return $this->cache->remember($cacheKey, function() {
            // In production, scrape from Bangladesh Jewellers Association
            // or integrate with commodity price APIs
            return [
                'gold' => [
                    '22k' => [
                        'price' => 102500,
                        'unit' => 'vori',
                        'unit_bn' => 'ভরি',
                        'change' => 500,
                        'change_percent' => 0.49,
                    ],
                    '21k' => [
                        'price' => 97850,
                        'unit' => 'vori',
                        'unit_bn' => 'ভরি',
                        'change' => 450,
                        'change_percent' => 0.46,
                    ],
                    '18k' => [
                        'price' => 83800,
                        'unit' => 'vori',
                        'unit_bn' => 'ভরি',
                        'change' => 400,
                        'change_percent' => 0.48,
                    ],
                ],
                'silver' => [
                    'price' => 1650,
                    'unit' => 'vori',
                    'unit_bn' => 'ভরি',
                    'change' => 20,
                    'change_percent' => 1.23,
                ],
                'fuel' => [
                    'petrol' => [
                        'price' => 130,
                        'unit' => 'liter',
                        'unit_bn' => 'লিটার',
                        'name_bn' => 'পেট্রোল (অকটেন)',
                    ],
                    'diesel' => [
                        'price' => 114,
                        'unit' => 'liter',
                        'unit_bn' => 'লিটার',
                        'name_bn' => 'ডিজেল',
                    ],
                    'kerosene' => [
                        'price' => 114,
                        'unit' => 'liter',
                        'unit_bn' => 'লিটার',
                        'name_bn' => 'কেরোসিন',
                    ],
                    'lpg' => [
                        'price' => 1275,
                        'unit' => '12kg cylinder',
                        'unit_bn' => '১২ কেজি সিলিন্ডার',
                        'name_bn' => 'এলপিজি',
                    ],
                ],
                'source' => 'Bangladesh Jewellers Association / BPC',
                'updated_at' => date('c'),
            ];
        }, 3600); // Cache for 1 hour
    }

    /**
     * Get emergency contact numbers
     */
    public function getEmergencyNumbers(): array
    {
        return [
            'categories' => [
                [
                    'name' => 'Emergency',
                    'name_bn' => 'জরুরি সেবা',
                    'icon' => 'alert',
                    'numbers' => [
                        ['name' => 'National Emergency', 'name_bn' => 'জাতীয় জরুরি সেবা', 'number' => '999', 'toll_free' => true],
                        ['name' => 'Fire Service', 'name_bn' => 'ফায়ার সার্ভিস', 'number' => '199', 'toll_free' => true],
                        ['name' => 'Ambulance', 'name_bn' => 'অ্যাম্বুলেন্স', 'number' => '199', 'toll_free' => true],
                    ],
                ],
                [
                    'name' => 'Police',
                    'name_bn' => 'পুলিশ',
                    'icon' => 'shield',
                    'numbers' => [
                        ['name' => 'Police Control Room', 'name_bn' => 'পুলিশ কন্ট্রোল রুম', 'number' => '100', 'toll_free' => true],
                        ['name' => 'RAB', 'name_bn' => 'র‍্যাব', 'number' => '01৭৭৯-৫২২২২২', 'toll_free' => false],
                        ['name' => 'Detective Branch', 'name_bn' => 'গোয়েন্দা পুলিশ', 'number' => '01৭৬৯-৬৯১৬২৯', 'toll_free' => false],
                    ],
                ],
                [
                    'name' => 'Women & Children',
                    'name_bn' => 'নারী ও শিশু',
                    'icon' => 'heart',
                    'numbers' => [
                        ['name' => 'Women Helpline', 'name_bn' => 'নারী নির্যাতন প্রতিরোধ', 'number' => '10921', 'toll_free' => true],
                        ['name' => 'Child Helpline', 'name_bn' => 'শিশু সহায়তা', 'number' => '1098', 'toll_free' => true],
                        ['name' => 'Anti-Trafficking', 'name_bn' => 'মানব পাচার রোধ', 'number' => '01৭৬০-৩২৩২৩৮', 'toll_free' => false],
                    ],
                ],
                [
                    'name' => 'Health',
                    'name_bn' => 'স্বাস্থ্য সেবা',
                    'icon' => 'health',
                    'numbers' => [
                        ['name' => 'Health Helpline', 'name_bn' => 'স্বাস্থ্য বাতায়ন', 'number' => '16789', 'toll_free' => true],
                        ['name' => 'Corona Helpline', 'name_bn' => 'করোনা হটলাইন', 'number' => '333', 'toll_free' => true],
                        ['name' => 'Mental Health', 'name_bn' => 'মানসিক স্বাস্থ্য', 'number' => '16789', 'toll_free' => true],
                    ],
                ],
                [
                    'name' => 'Utilities',
                    'name_bn' => 'সেবা প্রতিষ্ঠান',
                    'icon' => 'utility',
                    'numbers' => [
                        ['name' => 'DESCO (Electricity)', 'name_bn' => 'ডেসকো (বিদ্যুৎ)', 'number' => '16116', 'toll_free' => false],
                        ['name' => 'WASA (Water)', 'name_bn' => 'ওয়াসা (পানি)', 'number' => '16163', 'toll_free' => false],
                        ['name' => 'Titas Gas', 'name_bn' => 'তিতাস গ্যাস', 'number' => '16472', 'toll_free' => false],
                    ],
                ],
                [
                    'name' => 'Information',
                    'name_bn' => 'তথ্য সেবা',
                    'icon' => 'info',
                    'numbers' => [
                        ['name' => 'Govt Info', 'name_bn' => 'সরকারি তথ্য সেবা', 'number' => '333', 'toll_free' => true],
                        ['name' => 'Agriculture', 'name_bn' => 'কৃষি তথ্য সেবা', 'number' => '16123', 'toll_free' => true],
                        ['name' => 'Legal Aid', 'name_bn' => 'আইনি সহায়তা', 'number' => '16430', 'toll_free' => true],
                    ],
                ],
            ],
            'updated_at' => date('c'),
        ];
    }

    /**
     * Get public holidays for Bangladesh
     */
    public function getHolidays(?int $year = null): array
    {
        $year = $year ?? (int)date('Y');
        $cacheKey = "holidays_{$year}";
        
        return $this->cache->remember($cacheKey, function() use ($year) {
            // Bangladesh public holidays (dates may vary for lunar calendar holidays)
            $holidays = [
                ['date' => "{$year}-02-21", 'name' => 'International Mother Language Day', 'name_bn' => 'আন্তর্জাতিক মাতৃভাষা দিবস', 'type' => 'national'],
                ['date' => "{$year}-03-17", 'name' => "Sheikh Mujib's Birthday", 'name_bn' => 'জাতির পিতার জন্মদিন', 'type' => 'national'],
                ['date' => "{$year}-03-26", 'name' => 'Independence Day', 'name_bn' => 'স্বাধীনতা দিবস', 'type' => 'national'],
                ['date' => "{$year}-04-14", 'name' => 'Bengali New Year', 'name_bn' => 'পহেলা বৈশাখ', 'type' => 'national'],
                ['date' => "{$year}-05-01", 'name' => 'May Day', 'name_bn' => 'মে দিবস', 'type' => 'national'],
                ['date' => "{$year}-08-15", 'name' => 'National Mourning Day', 'name_bn' => 'জাতীয় শোক দিবস', 'type' => 'national'],
                ['date' => "{$year}-12-16", 'name' => 'Victory Day', 'name_bn' => 'বিজয় দিবস', 'type' => 'national'],
                ['date' => "{$year}-12-25", 'name' => 'Christmas', 'name_bn' => 'বড়দিন', 'type' => 'religious'],
                
                // Islamic holidays (approximate - actual dates depend on moon sighting)
                ['date' => "{$year}-03-31", 'name' => 'Eid ul-Fitr', 'name_bn' => 'ঈদুল ফিতর', 'type' => 'religious', 'lunar' => true],
                ['date' => "{$year}-04-01", 'name' => 'Eid ul-Fitr (2nd day)', 'name_bn' => 'ঈদুল ফিতর (২য় দিন)', 'type' => 'religious', 'lunar' => true],
                ['date' => "{$year}-06-07", 'name' => 'Eid ul-Adha', 'name_bn' => 'ঈদুল আযহা', 'type' => 'religious', 'lunar' => true],
                ['date' => "{$year}-06-08", 'name' => 'Eid ul-Adha (2nd day)', 'name_bn' => 'ঈদুল আযহা (২য় দিন)', 'type' => 'religious', 'lunar' => true],
                ['date' => "{$year}-06-09", 'name' => 'Eid ul-Adha (3rd day)', 'name_bn' => 'ঈদুল আযহা (৩য় দিন)', 'type' => 'religious', 'lunar' => true],
                ['date' => "{$year}-09-16", 'name' => 'Eid-e-Milad-un-Nabi', 'name_bn' => 'ঈদে মিলাদুন্নবী', 'type' => 'religious', 'lunar' => true],
                ['date' => "{$year}-07-17", 'name' => 'Shab-e-Meraj', 'name_bn' => 'শবে মেরাজ', 'type' => 'religious', 'lunar' => true],
                ['date' => "{$year}-09-07", 'name' => 'Ashura', 'name_bn' => 'আশুরা', 'type' => 'religious', 'lunar' => true],
                
                // Hindu holidays (approximate)
                ['date' => "{$year}-10-12", 'name' => 'Durga Puja', 'name_bn' => 'দুর্গাপূজা', 'type' => 'religious', 'lunar' => true],
                
                // Buddhist holidays
                ['date' => "{$year}-05-23", 'name' => 'Buddha Purnima', 'name_bn' => 'বুদ্ধ পূর্ণিমা', 'type' => 'religious', 'lunar' => true],
            ];
            
            // Sort by date
            usort($holidays, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));
            
            // Find next holiday
            $today = date('Y-m-d');
            $nextHoliday = null;
            foreach ($holidays as $holiday) {
                if ($holiday['date'] >= $today) {
                    $nextHoliday = $holiday;
                    $nextHoliday['days_until'] = (int)ceil((strtotime($holiday['date']) - strtotime($today)) / 86400);
                    break;
                }
            }
            
            return [
                'year' => $year,
                'holidays' => $holidays,
                'next_holiday' => $nextHoliday,
                'total' => count($holidays),
                'updated_at' => date('c'),
            ];
        }, 86400); // Cache for 24 hours
    }

    // =========== Search Methods ===========

    /**
     * Search across all portal content (news, jobs, education)
     */
    public function search(string $query, ?string $type = null, int $limit = 20): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return ['items' => [], 'total' => 0, 'query' => $query];
        }

        $results = [];
        $queryLower = mb_strtolower($query);

        // Search News
        if (!$type || $type === 'news' || $type === 'all') {
            try {
                $news = $this->getNews(null, null, 50);
                foreach ($news['items'] as $item) {
                    $title = mb_strtolower($item['title'] ?? '');
                    $summary = mb_strtolower($item['summary'] ?? '');
                    if (str_contains($title, $queryLower) || str_contains($summary, $queryLower)) {
                        $results[] = [
                            'type' => 'news',
                            'id' => $item['id'],
                            'title' => $item['title'],
                            'title_en' => $item['title_en'] ?? null,
                            'description' => $item['summary'],
                            'url' => $item['url'],
                            'image' => $item['image'] ?? null,
                            'source' => $item['source'] ?? null,
                            'date' => $item['published_at'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Skip on error
            }
        }

        // Search Jobs
        if (!$type || $type === 'jobs' || $type === 'all') {
            try {
                $jobs = $this->getJobs(null, 50);
                foreach ($jobs['items'] as $item) {
                    $title = mb_strtolower($item['title'] ?? '');
                    $org = mb_strtolower($item['organization'] ?? '');
                    if (str_contains($title, $queryLower) || str_contains($org, $queryLower)) {
                        $results[] = [
                            'type' => 'jobs',
                            'id' => $item['id'],
                            'title' => $item['title'],
                            'title_en' => $item['title_en'] ?? null,
                            'description' => $item['organization'],
                            'url' => $item['url'] ?? '/jobs',
                            'image' => null,
                            'source' => $item['type'] ?? null,
                            'date' => $item['deadline'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Skip on error
            }
        }

        // Search Education
        if (!$type || $type === 'education' || $type === 'all') {
            try {
                $edu = $this->getEducation(null, 50);
                foreach ($edu['items'] as $item) {
                    $title = mb_strtolower($item['title'] ?? '');
                    $desc = mb_strtolower($item['description'] ?? '');
                    if (str_contains($title, $queryLower) || str_contains($desc, $queryLower)) {
                        $results[] = [
                            'type' => 'education',
                            'id' => $item['id'],
                            'title' => $item['title'],
                            'title_en' => $item['title_en'] ?? null,
                            'description' => $item['description'],
                            'url' => $item['url'] ?? '/education',
                            'image' => null,
                            'source' => $item['type'] ?? null,
                            'date' => null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Skip on error
            }
        }

        return [
            'items' => array_slice($results, 0, $limit),
            'total' => count($results),
            'query' => $query,
            'type' => $type ?? 'all',
        ];
    }

    // =========== AI Assistant Methods ===========

    /**
     * AI chat with rate limiting
     */
    public function aiChat(string $message, string $clientId, bool $isAuthenticated): array
    {
        $ai = new OpenAIClient();

        // Check rate limit
        $rateLimit = $ai->checkRateLimit($clientId, $isAuthenticated);
        
        if (!$rateLimit['allowed']) {
            return [
                'success' => false,
                'error' => 'rate_limit_exceeded',
                'message' => $isAuthenticated
                    ? 'You have reached your daily limit of 20 questions. Please try again tomorrow.'
                    : 'You have reached the guest limit of 5 questions per day. Login for 20 questions/day!',
                'rate_limit' => $rateLimit,
            ];
        }

        // Get AI response
        $response = $ai->chat($message, $clientId);

        // Increment rate limit on success
        if ($response['success']) {
            $ai->incrementRateLimit($clientId);
        }

        // Get updated rate limit
        $updatedRateLimit = $ai->checkRateLimit($clientId, $isAuthenticated);

        return [
            'success' => $response['success'],
            'message' => $response['message'],
            'model' => $response['model'],
            'rate_limit' => $updatedRateLimit,
        ];
    }

    /**
     * Get AI rate limit status
     */
    public function getAiRateLimit(string $clientId, bool $isAuthenticated): array
    {
        $ai = new OpenAIClient();
        return $ai->checkRateLimit($clientId, $isAuthenticated);
    }
}
