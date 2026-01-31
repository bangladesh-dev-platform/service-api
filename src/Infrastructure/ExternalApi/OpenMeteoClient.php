<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

use App\Infrastructure\Data\BangladeshDistricts;

/**
 * Open-Meteo Weather API Client
 * Free weather API with no API key required
 * https://open-meteo.com/
 */
class OpenMeteoClient
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/forecast';
    
    // Default location: Dhaka, Bangladesh
    private const DEFAULT_LAT = 23.8103;
    private const DEFAULT_LON = 90.4125;

    private int $timeout;

    public function __construct(int $timeout = 10)
    {
        $this->timeout = $timeout;
    }

    /**
     * Get current weather and forecast
     */
    public function getWeather(float $lat = self::DEFAULT_LAT, float $lon = self::DEFAULT_LON, ?string $districtId = null): array
    {
        // If district ID provided, use its coordinates
        if ($districtId) {
            $district = BangladeshDistricts::findById($districtId);
            if ($district) {
                $lat = $district['lat'];
                $lon = $district['lon'];
            }
        }

        $params = http_build_query([
            'latitude' => $lat,
            'longitude' => $lon,
            'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,apparent_temperature',
            'daily' => 'temperature_2m_max,temperature_2m_min,weather_code,precipitation_probability_max',
            'timezone' => 'Asia/Dhaka',
            'forecast_days' => 5
        ]);

        $url = self::BASE_URL . '?' . $params;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => 'Accept: application/json'
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new \RuntimeException('Failed to fetch weather data from Open-Meteo');
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from Open-Meteo');
        }

        return $this->transformResponse($data, $lat, $lon);
    }

    /**
     * Transform Open-Meteo response to our format
     */
    private function transformResponse(array $data, float $lat, float $lon): array
    {
        $current = $data['current'] ?? [];
        $daily = $data['daily'] ?? [];
        
        // Find nearest district for location name
        $district = BangladeshDistricts::findNearest($lat, $lon);
        
        return [
            'location' => [
                'district_id' => $district['id'] ?? 'dhaka',
                'city' => $district['name'] ?? 'Dhaka',
                'city_bn' => $district['name_bn'] ?? 'ঢাকা',
                'division' => $district['division'] ?? 'Dhaka',
                'division_bn' => $district['division_bn'] ?? 'ঢাকা',
                'country' => 'Bangladesh',
                'country_bn' => 'বাংলাদেশ',
                'lat' => $data['latitude'] ?? $lat,
                'lon' => $data['longitude'] ?? $lon,
            ],
            'current' => [
                'temperature' => round($current['temperature_2m'] ?? 0),
                'feels_like' => round($current['apparent_temperature'] ?? $current['temperature_2m'] ?? 0),
                'humidity' => $current['relative_humidity_2m'] ?? 0,
                'wind_speed' => round($current['wind_speed_10m'] ?? 0),
                'weather_code' => $current['weather_code'] ?? 0,
                'condition' => $this->getConditionFromCode($current['weather_code'] ?? 0),
                'condition_bn' => $this->getConditionFromCode($current['weather_code'] ?? 0, 'bn'),
                'icon' => $this->getIconFromCode($current['weather_code'] ?? 0),
            ],
            'forecast' => $this->transformForecast($daily),
            'updated_at' => date('c'),
        ];
    }

    /**
     * Transform daily forecast data
     */
    private function transformForecast(array $daily): array
    {
        $forecast = [];
        $dates = $daily['time'] ?? [];
        $maxTemps = $daily['temperature_2m_max'] ?? [];
        $minTemps = $daily['temperature_2m_min'] ?? [];
        $codes = $daily['weather_code'] ?? [];
        $precipitation = $daily['precipitation_probability_max'] ?? [];

        for ($i = 0; $i < min(5, count($dates)); $i++) {
            $forecast[] = [
                'date' => $dates[$i] ?? '',
                'day' => $this->getDayName($dates[$i] ?? '', $i),
                'day_bn' => $this->getDayName($dates[$i] ?? '', $i, 'bn'),
                'temp_max' => round($maxTemps[$i] ?? 0),
                'temp_min' => round($minTemps[$i] ?? 0),
                'weather_code' => $codes[$i] ?? 0,
                'condition' => $this->getConditionFromCode($codes[$i] ?? 0),
                'condition_bn' => $this->getConditionFromCode($codes[$i] ?? 0, 'bn'),
                'icon' => $this->getIconFromCode($codes[$i] ?? 0),
                'precipitation_chance' => $precipitation[$i] ?? 0,
            ];
        }

        return $forecast;
    }

    /**
     * Get weather icon name from WMO code
     */
    private function getIconFromCode(int $code): string
    {
        // Map WMO codes to icon names (can be used with any icon library)
        $icons = [
            0 => 'sun',
            1 => 'sun',
            2 => 'cloud-sun',
            3 => 'cloud',
            45 => 'cloud-fog',
            48 => 'cloud-fog',
            51 => 'cloud-drizzle',
            53 => 'cloud-drizzle',
            55 => 'cloud-drizzle',
            61 => 'cloud-rain',
            63 => 'cloud-rain',
            65 => 'cloud-rain',
            71 => 'cloud-snow',
            73 => 'cloud-snow',
            75 => 'cloud-snow',
            80 => 'cloud-rain',
            81 => 'cloud-rain',
            82 => 'cloud-rain',
            95 => 'cloud-lightning',
            96 => 'cloud-lightning',
            99 => 'cloud-lightning',
        ];

        return $icons[$code] ?? 'cloud';
    }

    /**
     * Get human-readable condition from WMO weather code
     */
    private function getConditionFromCode(int $code, string $lang = 'en'): string
    {
        $conditions = [
            'en' => [
                0 => 'Clear sky',
                1 => 'Mainly clear',
                2 => 'Partly cloudy',
                3 => 'Overcast',
                45 => 'Foggy',
                48 => 'Foggy',
                51 => 'Light drizzle',
                53 => 'Drizzle',
                55 => 'Heavy drizzle',
                61 => 'Light rain',
                63 => 'Rain',
                65 => 'Heavy rain',
                71 => 'Light snow',
                73 => 'Snow',
                75 => 'Heavy snow',
                80 => 'Light showers',
                81 => 'Showers',
                82 => 'Heavy showers',
                95 => 'Thunderstorm',
                96 => 'Thunderstorm with hail',
                99 => 'Thunderstorm with hail',
            ],
            'bn' => [
                0 => 'পরিষ্কার আকাশ',
                1 => 'মূলত পরিষ্কার',
                2 => 'আংশিক মেঘলা',
                3 => 'মেঘাচ্ছন্ন',
                45 => 'কুয়াশা',
                48 => 'কুয়াশা',
                51 => 'হালকা গুঁড়ি বৃষ্টি',
                53 => 'গুঁড়ি বৃষ্টি',
                55 => 'ভারী গুঁড়ি বৃষ্টি',
                61 => 'হালকা বৃষ্টি',
                63 => 'বৃষ্টি',
                65 => 'ভারী বৃষ্টি',
                71 => 'হালকা তুষারপাত',
                73 => 'তুষারপাত',
                75 => 'ভারী তুষারপাত',
                80 => 'হালকা বর্ষণ',
                81 => 'বর্ষণ',
                82 => 'ভারী বর্ষণ',
                95 => 'বজ্রবৃষ্টি',
                96 => 'শিলাবৃষ্টিসহ বজ্রবৃষ্টি',
                99 => 'শিলাবৃষ্টিসহ বজ্রবৃষ্টি',
            ],
        ];

        $langConditions = $conditions[$lang] ?? $conditions['en'];
        return $langConditions[$code] ?? ($lang === 'bn' ? 'অজানা' : 'Unknown');
    }

    /**
     * Get day name from date
     */
    private function getDayName(string $date, int $index, string $lang = 'en'): string
    {
        $days = [
            'en' => ['Today', 'Tomorrow', 'Day After'],
            'bn' => ['আজ', 'কাল', 'পরশু'],
        ];

        if ($index < 3) {
            return $days[$lang][$index] ?? $days['en'][$index];
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '';
        }

        if ($lang === 'bn') {
            $dayNames = ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'];
            return $dayNames[(int)date('w', $timestamp)];
        }

        return date('l', $timestamp);
    }
}
