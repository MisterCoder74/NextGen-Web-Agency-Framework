<?php
/**
 * Security Helper for NextGen WebAgency Framework
 * Centralized security functions: API key validation, rate limiting, log sanitization.
 */

class SecurityHelper
{
    private static string $rateLimitDir = __DIR__ . '/../.rate_limit/';
    
    /**
     * Validate OpenAI API key format and length.
     * OpenAI keys start with 'sk-' and are typically 48+ characters.
     */
    public static function validateApiKey(string $key): array
    {
        $key = trim($key);
        
        // Check empty
        if (empty($key)) {
            return ['valid' => false, 'error' => 'API key is empty.'];
        }
        
        // Check prefix (sk- for standard keys)
        if (!str_starts_with($key, 'sk-')) {
            return ['valid' => false, 'error' => 'API key must start with "sk-".'];
        }
        
        // Check minimum length (40 chars minimum for security)
        if (strlen($key) < 40) {
            return ['valid' => false, 'error' => 'API key is too short. Minimum 40 characters required.'];
        }
        
        // Check maximum length (prevent buffer overflow)
        if (strlen($key) > 200) {
            return ['valid' => false, 'error' => 'API key exceeds maximum length.'];
        }
        
        // Validate characters (only alphanumeric, hyphens, underscores)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
            return ['valid' => false, 'error' => 'API key contains invalid characters.'];
        }
        
        return ['valid' => true, 'key' => $key];
    }
    
    /**
     * Validate GitHub token format and length.
     * GitHub tokens start with 'ghp_', 'gho_', 'ghu_', 'ghs_', or 'ghr_'.
     */
    public static function validateGitHubToken(string $token): array
    {
        $token = trim($token);
        
        // Check empty
        if (empty($token)) {
            return ['valid' => false, 'error' => 'GitHub token is empty.'];
        }
        
        // Check prefix (GitHub PAT prefixes)
        $validPrefixes = ['ghp_', 'gho_', 'ghu_', 'ghs_', 'ghr_'];
        $hasValidPrefix = false;
        foreach ($validPrefixes as $prefix) {
            if (str_starts_with($token, $prefix)) {
                $hasValidPrefix = true;
                break;
            }
        }
        
        if (!$hasValidPrefix) {
            return ['valid' => false, 'error' => 'GitHub token must start with a valid prefix (ghp_, gho_, ghu_, ghs_, ghr_).'];
        }
        
        // Check minimum length (40 chars for classic tokens)
        if (strlen($token) < 40) {
            return ['valid' => false, 'error' => 'GitHub token is too short. Minimum 40 characters required.'];
        }
        
        // Check maximum length
        if (strlen($token) > 200) {
            return ['valid' => false, 'error' => 'GitHub token exceeds maximum length.'];
        }
        
        // Validate characters
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $token)) {
            return ['valid' => false, 'error' => 'GitHub token contains invalid characters.'];
        }
        
        return ['valid' => true, 'token' => $token];
    }
    
    /**
     * Check and enforce rate limiting.
     * Returns true if request is allowed, false if rate limited.
     * Uses file-based sliding window counter.
     */
    public static function checkRateLimit(string $identifier, int $maxRequests = 30, int $windowSeconds = 60): array
    {
        // Ensure rate limit directory exists
        if (!is_dir(self::$rateLimitDir)) {
            mkdir(self::$rateLimitDir, 0750, true);
        }
        
        // Sanitize identifier for filename (use hash for security)
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $identifier);
        if (empty($safeId)) {
            $safeId = md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
        $hash = substr(hash('sha256', $safeId), 0, 16);
        $rateFile = self::$rateLimitDir . $hash . '.json';
        
        $now = time();
        $windowStart = $now - $windowSeconds;
        
        // Load existing requests
        $requests = [];
        if (file_exists($rateFile)) {
            $data = json_decode(file_get_contents($rateFile), true);
            if (is_array($data)) {
                // Filter to only requests within the window
                $requests = array_values(array_filter($data, function($ts) use ($windowStart) {
                    return $ts >= $windowStart;
                }));
            }
        }
        
        // Check rate limit
        if (count($requests) >= $maxRequests) {
            $oldest = min($requests);
            $retryAfter = ($oldest + $windowSeconds) - $now;
            return [
                'allowed' => false,
                'retry_after' => max(1, $retryAfter),
                'requests_count' => count($requests),
                'max_requests' => $maxRequests,
                'window_seconds' => $windowSeconds
            ];
        }
        
        // Add current request
        $requests[] = $now;
        
        // Save updated requests
        file_put_contents($rateFile, json_encode($requests), LOCK_EX);
        
        // Cleanup old rate files (random chance to prevent performance degradation)
        if (mt_rand(1, 100) <= 5) {
            self::cleanupOldRateFiles();
        }
        
        return [
            'allowed' => true,
            'requests_count' => count($requests),
            'max_requests' => $maxRequests,
            'remaining' => $maxRequests - count($requests)
        ];
    }
    
    /**
     * Clean up old rate limit files.
     */
    private static function cleanupOldRateFiles(): void
    {
        if (!is_dir(self::$rateLimitDir)) {
            return;
        }
        
        $files = glob(self::$rateLimitDir . '*.json');
        $now = time();
        $maxAge = 3600; // 1 hour
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
                @unlink($file);
            }
        }
    }
    
    /**
     * Sanitize data for logging - removes sensitive fields.
     * NEVER log API keys or tokens directly.
     */
    public static function sanitizeForLog(array $data, array $sensitiveFields = ['api_key', 'apiKey', 'Authorization', 'token', 'password']): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Check if this key is sensitive
            $isSensitive = false;
            foreach ($sensitiveFields as $field) {
                if (stripos($key, $field) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                // Redact sensitive values
                if (is_string($value) && strlen($value) > 4) {
                    $sanitized[$key] = substr($value, 0, 4) . '****' . substr($value, -4);
                } else {
                    $sanitized[$key] = '[REDACTED]';
                }
            } elseif (is_array($value)) {
                // Recursively sanitize nested arrays
                $sanitized[$key] = self::sanitizeForLog($value, $sensitiveFields);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get safe log prefix for audit logging (never includes credentials).
     */
    public static function getSafeLogContext(): array
    {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'None', 0, 200)
        ];
    }
    
    /**
     * Validate request body size.
     */
    public static function validateRequestSize(string $body, int $maxBytes = 1048576): array
    {
        $size = strlen($body);
        
        if ($size === 0) {
            return ['valid' => false, 'error' => 'Request body is empty.'];
        }
        
        if ($size > $maxBytes) {
            return [
                'valid' => false,
                'error' => "Request body exceeds maximum size of " . ($maxBytes / 1024) . " KB.",
                'size' => $size,
                'max_size' => $maxBytes
            ];
        }
        
        return ['valid' => true, 'size' => $size];
    }
    
    /**
     * Log event to audit log (with automatic sanitization).
     */
    public static function logEvent(string $action, string $username = 'Anonymous', array $params = []): void
    {
        $logFile = __DIR__ . '/../../audit_log.json';
        
        // Sanitize params before logging
        $safeParams = self::sanitizeForLog($params);
        
        $entry = array_merge(self::getSafeLogContext(), [
            'action' => $action,
            'user' => $username,
            'params' => $safeParams
        ]);
        
        $logs = [];
        if (file_exists($logFile)) {
            $logs = json_decode(file_get_contents($logFile), true) ?: [];
        }
        
        $logs[] = $entry;
        
        // Keep only last 10000 entries to prevent file bloat
        if (count($logs) > 10000) {
            $logs = array_slice($logs, -10000);
        }
        
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT), LOCK_EX);
    }
}
