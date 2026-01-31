<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

/**
 * Simple file-based cache for external API responses
 */
class FileCache
{
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct(?string $cacheDir = null, int $defaultTtl = 300)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/bdp_cache';
        $this->defaultTtl = $defaultTtl;
        
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cached value or execute callback and cache result
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Get value from cache
     */
    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = @unserialize($content);
        if ($data === false || !isset($data['expires_at'], $data['value'])) {
            @unlink($file);
            return null;
        }

        // Check expiration
        if ($data['expires_at'] < time()) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set value in cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $ttl = $ttl ?? $this->defaultTtl;

        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'cached_at' => date('c'),
        ];

        return @file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return @unlink($file);
        }
        
        return true;
    }

    /**
     * Clear all cached values
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            @unlink($file);
        }
        
        return true;
    }

    /**
     * Get cache file path for key
     */
    private function getFilePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cacheDir . '/' . $safeKey . '.cache';
    }
}
