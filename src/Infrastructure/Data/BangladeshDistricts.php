<?php

declare(strict_types=1);

namespace App\Infrastructure\Data;

/**
 * Bangladesh Districts with coordinates
 * All 64 districts with lat/lon for weather API
 */
class BangladeshDistricts
{
    /**
     * Get all districts grouped by division
     */
    public static function getAll(): array
    {
        return [
            'dhaka' => [
                'name' => 'Dhaka',
                'name_bn' => 'ঢাকা',
                'districts' => [
                    ['id' => 'dhaka', 'name' => 'Dhaka', 'name_bn' => 'ঢাকা', 'lat' => 23.8103, 'lon' => 90.4125],
                    ['id' => 'faridpur', 'name' => 'Faridpur', 'name_bn' => 'ফরিদপুর', 'lat' => 23.6070, 'lon' => 89.8429],
                    ['id' => 'gazipur', 'name' => 'Gazipur', 'name_bn' => 'গাজীপুর', 'lat' => 23.9999, 'lon' => 90.4203],
                    ['id' => 'gopalganj', 'name' => 'Gopalganj', 'name_bn' => 'গোপালগঞ্জ', 'lat' => 23.0488, 'lon' => 89.8266],
                    ['id' => 'kishoreganj', 'name' => 'Kishoreganj', 'name_bn' => 'কিশোরগঞ্জ', 'lat' => 24.4449, 'lon' => 90.7766],
                    ['id' => 'madaripur', 'name' => 'Madaripur', 'name_bn' => 'মাদারীপুর', 'lat' => 23.1641, 'lon' => 90.1897],
                    ['id' => 'manikganj', 'name' => 'Manikganj', 'name_bn' => 'মানিকগঞ্জ', 'lat' => 23.8617, 'lon' => 90.0003],
                    ['id' => 'munshiganj', 'name' => 'Munshiganj', 'name_bn' => 'মুন্সিগঞ্জ', 'lat' => 23.5422, 'lon' => 90.5305],
                    ['id' => 'narayanganj', 'name' => 'Narayanganj', 'name_bn' => 'নারায়ণগঞ্জ', 'lat' => 23.6238, 'lon' => 90.5000],
                    ['id' => 'narsingdi', 'name' => 'Narsingdi', 'name_bn' => 'নরসিংদী', 'lat' => 23.9322, 'lon' => 90.7151],
                    ['id' => 'rajbari', 'name' => 'Rajbari', 'name_bn' => 'রাজবাড়ী', 'lat' => 23.7574, 'lon' => 89.6445],
                    ['id' => 'shariatpur', 'name' => 'Shariatpur', 'name_bn' => 'শরীয়তপুর', 'lat' => 23.2423, 'lon' => 90.4348],
                    ['id' => 'tangail', 'name' => 'Tangail', 'name_bn' => 'টাঙ্গাইল', 'lat' => 24.2513, 'lon' => 89.9167],
                ],
            ],
            'chattogram' => [
                'name' => 'Chattogram',
                'name_bn' => 'চট্টগ্রাম',
                'districts' => [
                    ['id' => 'chattogram', 'name' => 'Chattogram', 'name_bn' => 'চট্টগ্রাম', 'lat' => 22.3569, 'lon' => 91.7832],
                    ['id' => 'bandarban', 'name' => 'Bandarban', 'name_bn' => 'বান্দরবান', 'lat' => 22.1953, 'lon' => 92.2184],
                    ['id' => 'brahmanbaria', 'name' => 'Brahmanbaria', 'name_bn' => 'ব্রাহ্মণবাড়িয়া', 'lat' => 23.9571, 'lon' => 91.1115],
                    ['id' => 'chandpur', 'name' => 'Chandpur', 'name_bn' => 'চাঁদপুর', 'lat' => 23.2333, 'lon' => 90.6712],
                    ['id' => 'comilla', 'name' => 'Comilla', 'name_bn' => 'কুমিল্লা', 'lat' => 23.4607, 'lon' => 91.1809],
                    ['id' => 'coxsbazar', 'name' => "Cox's Bazar", 'name_bn' => 'কক্সবাজার', 'lat' => 21.4272, 'lon' => 92.0058],
                    ['id' => 'feni', 'name' => 'Feni', 'name_bn' => 'ফেনী', 'lat' => 23.0159, 'lon' => 91.3976],
                    ['id' => 'khagrachhari', 'name' => 'Khagrachhari', 'name_bn' => 'খাগড়াছড়ি', 'lat' => 23.1193, 'lon' => 91.9847],
                    ['id' => 'lakshmipur', 'name' => 'Lakshmipur', 'name_bn' => 'লক্ষ্মীপুর', 'lat' => 22.9447, 'lon' => 90.8282],
                    ['id' => 'noakhali', 'name' => 'Noakhali', 'name_bn' => 'নোয়াখালী', 'lat' => 22.8696, 'lon' => 91.0995],
                    ['id' => 'rangamati', 'name' => 'Rangamati', 'name_bn' => 'রাঙ্গামাটি', 'lat' => 22.7324, 'lon' => 92.2985],
                ],
            ],
            'rajshahi' => [
                'name' => 'Rajshahi',
                'name_bn' => 'রাজশাহী',
                'districts' => [
                    ['id' => 'rajshahi', 'name' => 'Rajshahi', 'name_bn' => 'রাজশাহী', 'lat' => 24.3745, 'lon' => 88.6042],
                    ['id' => 'bogura', 'name' => 'Bogura', 'name_bn' => 'বগুড়া', 'lat' => 24.8510, 'lon' => 89.3697],
                    ['id' => 'chapainawabganj', 'name' => 'Chapainawabganj', 'name_bn' => 'চাঁপাইনবাবগঞ্জ', 'lat' => 24.5965, 'lon' => 88.2776],
                    ['id' => 'joypurhat', 'name' => 'Joypurhat', 'name_bn' => 'জয়পুরহাট', 'lat' => 25.0968, 'lon' => 89.0227],
                    ['id' => 'naogaon', 'name' => 'Naogaon', 'name_bn' => 'নওগাঁ', 'lat' => 24.7936, 'lon' => 88.9318],
                    ['id' => 'natore', 'name' => 'Natore', 'name_bn' => 'নাটোর', 'lat' => 24.4206, 'lon' => 89.0000],
                    ['id' => 'nawabganj', 'name' => 'Nawabganj', 'name_bn' => 'নবাবগঞ্জ', 'lat' => 24.5943, 'lon' => 88.2775],
                    ['id' => 'pabna', 'name' => 'Pabna', 'name_bn' => 'পাবনা', 'lat' => 24.0064, 'lon' => 89.2372],
                    ['id' => 'sirajganj', 'name' => 'Sirajganj', 'name_bn' => 'সিরাজগঞ্জ', 'lat' => 24.4534, 'lon' => 89.7006],
                ],
            ],
            'khulna' => [
                'name' => 'Khulna',
                'name_bn' => 'খুলনা',
                'districts' => [
                    ['id' => 'khulna', 'name' => 'Khulna', 'name_bn' => 'খুলনা', 'lat' => 22.8456, 'lon' => 89.5403],
                    ['id' => 'bagerhat', 'name' => 'Bagerhat', 'name_bn' => 'বাগেরহাট', 'lat' => 22.4000, 'lon' => 89.7500],
                    ['id' => 'chuadanga', 'name' => 'Chuadanga', 'name_bn' => 'চুয়াডাঙ্গা', 'lat' => 23.6161, 'lon' => 88.8263],
                    ['id' => 'jessore', 'name' => 'Jessore', 'name_bn' => 'যশোর', 'lat' => 23.1667, 'lon' => 89.2167],
                    ['id' => 'jhenaidah', 'name' => 'Jhenaidah', 'name_bn' => 'ঝিনাইদহ', 'lat' => 23.5440, 'lon' => 89.1539],
                    ['id' => 'kushtia', 'name' => 'Kushtia', 'name_bn' => 'কুষ্টিয়া', 'lat' => 23.9013, 'lon' => 89.1206],
                    ['id' => 'magura', 'name' => 'Magura', 'name_bn' => 'মাগুরা', 'lat' => 23.4833, 'lon' => 89.4167],
                    ['id' => 'meherpur', 'name' => 'Meherpur', 'name_bn' => 'মেহেরপুর', 'lat' => 23.7622, 'lon' => 88.6318],
                    ['id' => 'narail', 'name' => 'Narail', 'name_bn' => 'নড়াইল', 'lat' => 23.1667, 'lon' => 89.5833],
                    ['id' => 'satkhira', 'name' => 'Satkhira', 'name_bn' => 'সাতক্ষীরা', 'lat' => 22.7185, 'lon' => 89.0705],
                ],
            ],
            'barishal' => [
                'name' => 'Barishal',
                'name_bn' => 'বরিশাল',
                'districts' => [
                    ['id' => 'barishal', 'name' => 'Barishal', 'name_bn' => 'বরিশাল', 'lat' => 22.7010, 'lon' => 90.3535],
                    ['id' => 'barguna', 'name' => 'Barguna', 'name_bn' => 'বরগুনা', 'lat' => 22.0953, 'lon' => 90.1121],
                    ['id' => 'bhola', 'name' => 'Bhola', 'name_bn' => 'ভোলা', 'lat' => 22.6859, 'lon' => 90.6482],
                    ['id' => 'jhalokati', 'name' => 'Jhalokati', 'name_bn' => 'ঝালকাঠি', 'lat' => 22.6406, 'lon' => 90.1987],
                    ['id' => 'patuakhali', 'name' => 'Patuakhali', 'name_bn' => 'পটুয়াখালী', 'lat' => 22.3596, 'lon' => 90.3290],
                    ['id' => 'pirojpur', 'name' => 'Pirojpur', 'name_bn' => 'পিরোজপুর', 'lat' => 22.5841, 'lon' => 89.9720],
                ],
            ],
            'sylhet' => [
                'name' => 'Sylhet',
                'name_bn' => 'সিলেট',
                'districts' => [
                    ['id' => 'sylhet', 'name' => 'Sylhet', 'name_bn' => 'সিলেট', 'lat' => 24.8949, 'lon' => 91.8687],
                    ['id' => 'habiganj', 'name' => 'Habiganj', 'name_bn' => 'হবিগঞ্জ', 'lat' => 24.3840, 'lon' => 91.4163],
                    ['id' => 'moulvibazar', 'name' => 'Moulvibazar', 'name_bn' => 'মৌলভীবাজার', 'lat' => 24.4829, 'lon' => 91.7774],
                    ['id' => 'sunamganj', 'name' => 'Sunamganj', 'name_bn' => 'সুনামগঞ্জ', 'lat' => 25.0658, 'lon' => 91.3950],
                ],
            ],
            'rangpur' => [
                'name' => 'Rangpur',
                'name_bn' => 'রংপুর',
                'districts' => [
                    ['id' => 'rangpur', 'name' => 'Rangpur', 'name_bn' => 'রংপুর', 'lat' => 25.7439, 'lon' => 89.2752],
                    ['id' => 'dinajpur', 'name' => 'Dinajpur', 'name_bn' => 'দিনাজপুর', 'lat' => 25.6279, 'lon' => 88.6332],
                    ['id' => 'gaibandha', 'name' => 'Gaibandha', 'name_bn' => 'গাইবান্ধা', 'lat' => 25.3288, 'lon' => 89.5280],
                    ['id' => 'kurigram', 'name' => 'Kurigram', 'name_bn' => 'কুড়িগ্রাম', 'lat' => 25.8072, 'lon' => 89.6297],
                    ['id' => 'lalmonirhat', 'name' => 'Lalmonirhat', 'name_bn' => 'লালমনিরহাট', 'lat' => 25.9923, 'lon' => 89.2847],
                    ['id' => 'nilphamari', 'name' => 'Nilphamari', 'name_bn' => 'নীলফামারী', 'lat' => 25.9310, 'lon' => 88.8560],
                    ['id' => 'panchagarh', 'name' => 'Panchagarh', 'name_bn' => 'পঞ্চগড়', 'lat' => 26.3411, 'lon' => 88.5542],
                    ['id' => 'thakurgaon', 'name' => 'Thakurgaon', 'name_bn' => 'ঠাকুরগাঁও', 'lat' => 26.0336, 'lon' => 88.4616],
                ],
            ],
            'mymensingh' => [
                'name' => 'Mymensingh',
                'name_bn' => 'ময়মনসিংহ',
                'districts' => [
                    ['id' => 'mymensingh', 'name' => 'Mymensingh', 'name_bn' => 'ময়মনসিংহ', 'lat' => 24.7471, 'lon' => 90.4203],
                    ['id' => 'jamalpur', 'name' => 'Jamalpur', 'name_bn' => 'জামালপুর', 'lat' => 24.9375, 'lon' => 89.9372],
                    ['id' => 'netrokona', 'name' => 'Netrokona', 'name_bn' => 'নেত্রকোণা', 'lat' => 24.8703, 'lon' => 90.7278],
                    ['id' => 'sherpur', 'name' => 'Sherpur', 'name_bn' => 'শেরপুর', 'lat' => 25.0204, 'lon' => 90.0152],
                ],
            ],
        ];
    }

    /**
     * Get flat list of all districts
     */
    public static function getAllFlat(): array
    {
        $districts = [];
        foreach (self::getAll() as $division) {
            foreach ($division['districts'] as $district) {
                $district['division'] = $division['name'];
                $district['division_bn'] = $division['name_bn'];
                $districts[] = $district;
            }
        }
        return $districts;
    }

    /**
     * Find district by ID
     */
    public static function findById(string $id): ?array
    {
        foreach (self::getAllFlat() as $district) {
            if ($district['id'] === $id) {
                return $district;
            }
        }
        return null;
    }

    /**
     * Find nearest district to given coordinates
     */
    public static function findNearest(float $lat, float $lon): ?array
    {
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach (self::getAllFlat() as $district) {
            $distance = self::haversineDistance($lat, $lon, $district['lat'], $district['lon']);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $district;
            }
        }

        return $nearest;
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
