<?php

declare(strict_types=1);

namespace App\Presentation\Controllers\Portal;

use App\Application\Portal\PortalService;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Portal Controller - handles all homepage data endpoints
 */
class PortalController
{
    private PortalService $portalService;

    public function __construct(PortalService $portalService)
    {
        $this->portalService = $portalService;
    }

    /**
     * GET /api/v1/portal/weather
     * Query params: lat, lon, district (district ID)
     */
    public function weather(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $lat = isset($params['lat']) ? (float)$params['lat'] : null;
            $lon = isset($params['lon']) ? (float)$params['lon'] : null;
            $districtId = $params['district'] ?? null;

            $data = $this->portalService->getWeather($lat, $lon, $districtId);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'WEATHER_ERROR',
                'Failed to fetch weather data: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/districts
     * Returns all districts grouped by division
     */
    public function districts(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $flat = isset($params['flat']) && $params['flat'] === 'true';

            $data = $flat 
                ? $this->portalService->getDistrictsList()
                : $this->portalService->getDistricts();
                
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'DISTRICTS_ERROR',
                'Failed to fetch districts: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/weather/locations
     * Returns 8 divisions + Cumilla for homepage dropdown
     */
    public function weatherLocations(Request $request, Response $response): Response
    {
        try {
            $data = $this->portalService->getWeatherLocations();
            return JsonResponse::success($response, ['locations' => $data]);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'LOCATIONS_ERROR',
                'Failed to fetch weather locations: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/weather/bulk
     * Returns weather for all districts (for weather page)
     * Query params: division (filter by division)
     */
    public function weatherBulk(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $division = $params['division'] ?? null;

            $data = $this->portalService->getWeatherBulk($division);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'WEATHER_BULK_ERROR',
                'Failed to fetch bulk weather data: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/weather/divisions
     * Returns weather for 8 division capitals + Cumilla
     */
    public function weatherDivisions(Request $request, Response $response): Response
    {
        try {
            $data = $this->portalService->getWeatherDivisions();
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'WEATHER_DIVISIONS_ERROR',
                'Failed to fetch divisions weather: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/currency
     */
    public function currency(Request $request, Response $response): Response
    {
        try {
            $data = $this->portalService->getCurrency();
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'CURRENCY_ERROR',
                'Failed to fetch currency data: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/news
     * Query params: category, source, limit
     */
    public function news(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $category = $params['category'] ?? null;
            $source = $params['source'] ?? null;
            $limit = min((int)($params['limit'] ?? 10), 50);

            $data = $this->portalService->getNews($category, $source, $limit);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'NEWS_ERROR',
                'Failed to fetch news: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/radio
     */
    public function radio(Request $request, Response $response): Response
    {
        try {
            $data = $this->portalService->getRadioStations();
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'RADIO_ERROR',
                'Failed to fetch radio stations: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/jobs
     */
    public function jobs(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $type = $params['type'] ?? null;
            $limit = min((int)($params['limit'] ?? 10), 50);

            $data = $this->portalService->getJobs($type, $limit);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'JOBS_ERROR',
                'Failed to fetch jobs: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/notices
     */
    public function notices(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $category = $params['category'] ?? null;
            $limit = min((int)($params['limit'] ?? 10), 50);

            $data = $this->portalService->getNotices($category, $limit);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'NOTICES_ERROR',
                'Failed to fetch notices: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/education
     */
    public function education(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $type = $params['type'] ?? null;
            $limit = min((int)($params['limit'] ?? 10), 50);

            $data = $this->portalService->getEducation($type, $limit);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'EDUCATION_ERROR',
                'Failed to fetch education content: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/market
     */
    public function market(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $category = $params['category'] ?? null;
            $limit = min((int)($params['limit'] ?? 10), 50);

            $data = $this->portalService->getMarketDeals($category, $limit);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'MARKET_ERROR',
                'Failed to fetch market deals: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/all
     * Returns all portal data in a single request (for initial page load)
     */
    public function all(Request $request, Response $response): Response
    {
        try {
            $data = [
                'weather' => $this->portalService->getWeather(),
                'currency' => $this->portalService->getCurrency(),
                'news' => $this->portalService->getNews(null, null, 5),
                'radio' => $this->portalService->getRadioStations(),
                'jobs' => $this->portalService->getJobs(null, 4),
                'notices' => $this->portalService->getNotices(null, 4),
                'education' => $this->portalService->getEducation(null, 4),
                'market' => $this->portalService->getMarketDeals(null, 4),
            ];

            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'PORTAL_ERROR',
                'Failed to fetch portal data: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/prayer
     * Query params: city
     */
    public function prayer(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $city = $params['city'] ?? 'Dhaka';

            $data = $this->portalService->getPrayerTimes($city);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'PRAYER_ERROR',
                'Failed to fetch prayer times: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/cricket
     */
    public function cricket(Request $request, Response $response): Response
    {
        try {
            $data = $this->portalService->getCricketScores();
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'CRICKET_ERROR',
                'Failed to fetch cricket scores: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/commodities
     */
    public function commodities(Request $request, Response $response): Response
    {
        try {
            $data = $this->portalService->getCommodityPrices();
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'COMMODITIES_ERROR',
                'Failed to fetch commodity prices: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/emergency
     */
    public function emergency(Request $request, Response $response): Response
    {
        try {
            $data = $this->portalService->getEmergencyNumbers();
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'EMERGENCY_ERROR',
                'Failed to fetch emergency numbers: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/holidays
     * Query params: year
     */
    public function holidays(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $year = isset($params['year']) ? (int)$params['year'] : null;

            $data = $this->portalService->getHolidays($year);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'HOLIDAYS_ERROR',
                'Failed to fetch holidays: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/search
     * Query params: q (query), type (news|jobs|education|all), limit
     */
    public function search(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $query = $params['q'] ?? '';
            $type = $params['type'] ?? 'all';
            $limit = min((int)($params['limit'] ?? 20), 50);

            if (strlen(trim($query)) < 2) {
                return JsonResponse::error(
                    $response,
                    'INVALID_QUERY',
                    'Search query must be at least 2 characters',
                    null,
                    400
                );
            }

            $data = $this->portalService->search($query, $type, $limit);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'SEARCH_ERROR',
                'Search failed: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * POST /api/v1/portal/ai/chat
     * Body: { message: string }
     */
    public function aiChat(Request $request, Response $response): Response
    {
        try {
            $body = $request->getParsedBody();
            $message = $body['message'] ?? '';

            if (strlen(trim($message)) < 2) {
                return JsonResponse::error(
                    $response,
                    'INVALID_MESSAGE',
                    'Message must be at least 2 characters',
                    null,
                    400
                );
            }

            if (strlen($message) > 500) {
                return JsonResponse::error(
                    $response,
                    'MESSAGE_TOO_LONG',
                    'Message must be less than 500 characters',
                    null,
                    400
                );
            }

            // Get client identifier (IP or user ID from JWT)
            $clientId = $request->getAttribute('user_id') 
                ?? $request->getServerParams()['REMOTE_ADDR'] 
                ?? 'unknown';
            
            $isAuthenticated = $request->getAttribute('user_id') !== null;

            $data = $this->portalService->aiChat($message, $clientId, $isAuthenticated);
            
            if (!$data['success'] && ($data['error'] ?? '') === 'rate_limit_exceeded') {
                return JsonResponse::error(
                    $response,
                    'RATE_LIMIT_EXCEEDED',
                    $data['message'],
                    ['rate_limit' => $data['rate_limit']],
                    429
                );
            }

            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'AI_ERROR',
                'AI service error: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * GET /api/v1/portal/ai/limit
     * Get current rate limit status
     */
    public function aiRateLimit(Request $request, Response $response): Response
    {
        try {
            $clientId = $request->getAttribute('user_id') 
                ?? $request->getServerParams()['REMOTE_ADDR'] 
                ?? 'unknown';
            
            $isAuthenticated = $request->getAttribute('user_id') !== null;

            $data = $this->portalService->getAiRateLimit($clientId, $isAuthenticated);
            return JsonResponse::success($response, $data);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                $response,
                'RATE_LIMIT_ERROR',
                'Failed to get rate limit: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}
