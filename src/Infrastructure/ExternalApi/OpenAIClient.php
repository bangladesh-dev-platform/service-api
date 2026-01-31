<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

/**
 * OpenAI API Client with rate limiting
 * Uses GPT-4o-mini for cost efficiency and quality
 */
class OpenAIClient
{
    private string $apiKey;
    private int $maxTokens;
    private string $model;
    private int $timeout;

    // Rate limits
    private const GUEST_DAILY_LIMIT = 5;
    private const USER_DAILY_LIMIT = 20;
    private const RATE_LIMIT_WINDOW = 86400; // 24 hours

    // System prompt for Bangladesh context
    private const SYSTEM_PROMPT = <<<EOT
You are a helpful assistant for banglade.sh, a Bangladesh portal website. 
You help users with questions about Bangladesh - culture, travel, government services, education, jobs, and general information.
Keep responses brief, helpful, and friendly. 
If asked about topics unrelated to Bangladesh or harmful content, politely redirect to Bangladesh-related topics.
Respond in the same language the user writes in (Bengali or English).
EOT;

    public function __construct(
        ?string $apiKey = null,
        int $maxTokens = 250,
        string $model = 'gpt-4o-mini',
        int $timeout = 30
    ) {
        $this->apiKey = $apiKey ?? ($_ENV['OPENAI_API_KEY'] ?? '');
        $this->maxTokens = $maxTokens;
        $this->model = $model;
        $this->timeout = $timeout;
    }

    /**
     * Check if API is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get rate limit for user type
     */
    public function getRateLimit(bool $isAuthenticated): int
    {
        return $isAuthenticated ? self::USER_DAILY_LIMIT : self::GUEST_DAILY_LIMIT;
    }

    /**
     * Check rate limit using file-based storage
     */
    public function checkRateLimit(string $identifier, bool $isAuthenticated): array
    {
        $limit = $this->getRateLimit($isAuthenticated);
        $cacheDir = sys_get_temp_dir() . '/bdp_ai_limits';
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . '/' . md5($identifier) . '.json';
        $now = time();
        $data = ['count' => 0, 'reset_at' => $now + self::RATE_LIMIT_WINDOW];

        if (file_exists($cacheFile)) {
            $stored = json_decode(file_get_contents($cacheFile), true);
            if ($stored && $stored['reset_at'] > $now) {
                $data = $stored;
            }
        }

        return [
            'allowed' => $data['count'] < $limit,
            'remaining' => max(0, $limit - $data['count']),
            'limit' => $limit,
            'reset_at' => date('c', $data['reset_at']),
            'reset_in_seconds' => max(0, $data['reset_at'] - $now),
        ];
    }

    /**
     * Increment rate limit counter
     */
    public function incrementRateLimit(string $identifier): void
    {
        $cacheDir = sys_get_temp_dir() . '/bdp_ai_limits';
        $cacheFile = $cacheDir . '/' . md5($identifier) . '.json';
        $now = time();
        $data = ['count' => 0, 'reset_at' => $now + self::RATE_LIMIT_WINDOW];

        if (file_exists($cacheFile)) {
            $stored = json_decode(file_get_contents($cacheFile), true);
            if ($stored && $stored['reset_at'] > $now) {
                $data = $stored;
            }
        }

        $data['count']++;
        file_put_contents($cacheFile, json_encode($data));
    }

    /**
     * Send chat completion request
     */
    public function chat(string $message, ?string $userId = null): array
    {
        if (!$this->isConfigured()) {
            return $this->getFallbackResponse($message);
        }

        try {
            $ch = curl_init();
            
            // Build request payload - GPT-5 models use max_completion_tokens
            $payload = [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => $this->sanitizeInput($message)],
                ],
            ];
            
            // GPT-5+ uses max_completion_tokens, older models use max_tokens
            if (str_starts_with($this->model, 'gpt-5') || str_starts_with($this->model, 'o1') || str_starts_with($this->model, 'o3') || str_starts_with($this->model, 'o4')) {
                $payload['max_completion_tokens'] = $this->maxTokens;
            } else {
                $payload['max_tokens'] = $this->maxTokens;
                $payload['temperature'] = 0.7;
            }
            
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode !== 200 || $response === false) {
                error_log("OpenAI API error: HTTP $httpCode - $error");
                return $this->getFallbackResponse($message);
            }

            $data = json_decode($response, true);
            
            if (!isset($data['choices'][0]['message']['content'])) {
                return $this->getFallbackResponse($message);
            }

            return [
                'success' => true,
                'message' => trim($data['choices'][0]['message']['content']),
                'model' => $this->model,
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            ];

        } catch (\Throwable $e) {
            error_log("OpenAI exception: " . $e->getMessage());
            return $this->getFallbackResponse($message);
        }
    }

    /**
     * Sanitize user input
     */
    private function sanitizeInput(string $input): string
    {
        // Limit length
        $input = mb_substr($input, 0, 500);
        // Remove potential injection attempts
        $input = strip_tags($input);
        return trim($input);
    }

    /**
     * Fallback response when API is unavailable
     */
    private function getFallbackResponse(string $message): array
    {
        // Simple keyword-based fallback responses
        $message = mb_strtolower($message);
        
        $responses = [
            'weather' => 'For weather information, please check our Weather section on the homepage. We provide forecasts for all 64 districts of Bangladesh.',
            'আবহাওয়া' => 'আবহাওয়ার তথ্যের জন্য, অনুগ্রহ করে হোমপেজে আবহাওয়া বিভাগটি দেখুন। আমরা বাংলাদেশের ৬৪ জেলার পূর্বাভাস প্রদান করি।',
            'job' => 'Looking for jobs? Check our Jobs section for the latest government, private, and NGO positions in Bangladesh.',
            'চাকরি' => 'চাকরি খুঁজছেন? বাংলাদেশে সরকারি, বেসরকারি এবং এনজিও পদের জন্য আমাদের চাকরি বিভাগটি দেখুন।',
            'news' => 'For the latest news from Bangladesh, visit our News section. We aggregate from Prothom Alo, Kaler Kantho, and more.',
            'prayer' => 'Prayer times are available on our homepage. We show accurate namaz times for major cities in Bangladesh.',
            'নামাজ' => 'নামাজের সময় আমাদের হোমপেজে পাওয়া যায়। আমরা বাংলাদেশের প্রধান শহরগুলির জন্য সঠিক নামাজের সময় দেখাই।',
            'hello' => 'Hello! Welcome to banglade.sh. How can I help you today? You can ask about weather, jobs, news, or anything about Bangladesh!',
            'হ্যালো' => 'হ্যালো! banglade.sh এ স্বাগতম। আজ আমি কীভাবে আপনাকে সাহায্য করতে পারি? আপনি আবহাওয়া, চাকরি, সংবাদ বা বাংলাদেশ সম্পর্কে যেকোনো কিছু জিজ্ঞাসা করতে পারেন!',
        ];

        foreach ($responses as $keyword => $response) {
            if (str_contains($message, $keyword)) {
                return [
                    'success' => true,
                    'message' => $response,
                    'model' => 'fallback',
                    'tokens_used' => 0,
                ];
            }
        }

        // Default response
        $isBangla = preg_match('/[\x{0980}-\x{09FF}]/u', $message);
        
        return [
            'success' => true,
            'message' => $isBangla 
                ? 'দুঃখিত, আমি এই মুহূর্তে সীমিত উত্তর দিতে পারছি। অনুগ্রহ করে আমাদের হোমপেজের বিভিন্ন বিভাগ দেখুন - আবহাওয়া, সংবাদ, চাকরি এবং আরও অনেক কিছু!'
                : 'Sorry, I can only provide limited responses at the moment. Please explore our homepage sections - weather, news, jobs, and more!',
            'model' => 'fallback',
            'tokens_used' => 0,
        ];
    }
}
