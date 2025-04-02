<?php

namespace App\Service;

class JWT 
{
    /**
     * Simple JWT encode function to use as fallback when Firebase JWT isn't available
     */
    public static function encode($payload, $key, $alg = 'HS256') 
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $alg
        ];
        
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $key, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Simple JWT decode function
     */
    public static function decode($jwt, $key, $allowed_algs = ['HS256']) 
    {
        $parts = explode('.', $jwt);
        if (count($parts) != 3) {
            throw new \Exception('Wrong number of segments');
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        $header = json_decode(self::base64UrlDecode($headerEncoded), true);
        if (!$header) {
            throw new \Exception('Invalid header encoding');
        }
        
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload) {
            throw new \Exception('Invalid payload encoding');
        }
        
        $signature = self::base64UrlDecode($signatureEncoded);
        
        // Verify signature
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $key, true);
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Signature verification failed');
        }
        
        // Verify expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \Exception('Token expired');
        }
        
        return (object) $payload;
    }
    
    private static function base64UrlEncode($data) 
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
    
    private static function base64UrlDecode($data) 
    {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
