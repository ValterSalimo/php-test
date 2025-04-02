<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Service for rate limiting
 */
class RateLimitService
{
    private $redis;
    private $maxRequests;
    private $timeWindowSeconds;
    
    public function __construct($redis, int $maxRequests = 60, int $timeWindowSeconds = 60)
    {
        $this->redis = $redis;
        $this->maxRequests = $maxRequests;
        $this->timeWindowSeconds = $timeWindowSeconds;
    }
    
    /**
     * Check if the request is rate limited
     */
    public function isRateLimited(string $key): bool
    {
        try {
            $currentCount = (int)$this->redis->get("rate_limit:$key") ?: 0;
            
            if ($currentCount >= $this->maxRequests) {
                return true;
            }
            
            $this->redis->incr("rate_limit:$key");
            
            // Set expiry on first hit
            if ($currentCount === 0) {
                $this->redis->expire("rate_limit:$key", $this->timeWindowSeconds);
            }
            
            return false;
        } catch (\Exception $e) {
            // If something goes wrong, we default to not rate limiting
            return false;
        }
    }
    
    /**
     * Get remaining requests for the key
     */
    public function getRemainingRequests(string $key): int
    {
        try {
            $currentCount = (int)$this->redis->get("rate_limit:$key") ?: 0;
            return max(0, $this->maxRequests - $currentCount);
        } catch (\Exception $e) {
            // If something goes wrong, assume all requests are available
            return $this->maxRequests;
        }
    }
    
    /**
     * Get reset time for the key
     */
    public function getResetTime(string $key): int
    {
        try {
            return $this->redis->ttl("rate_limit:$key") ?: $this->timeWindowSeconds;
        } catch (\Exception $e) {
            // If something goes wrong, default to the window size
            return $this->timeWindowSeconds;
        }
    }
}
