<?php

namespace App\Service;

use App\Database\DatabaseInterface;
use App\Service\LogService;

class AuthService
{
    private $db;
    private $jwtSecret;
    private $tokenExpiration;
    private $logger;

    public function __construct(DatabaseInterface $db, $jwtSecret, $logger = null, $tokenExpiration = 3600)
    {
        $this->db = $db;
        $this->jwtSecret = $jwtSecret;
        $this->logger = $logger;
        $this->tokenExpiration = $tokenExpiration;
    }

    public function authenticate($username, $password)
    {
        // Verify credentials and generate token
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            if ($this->logger) {
                $this->logger->warning("Authentication failed for user: " . $username);
            }
            throw new \Exception('Invalid credentials');
        }
        
        if ($this->logger) {
            $this->logger->info("User authenticated successfully: " . $username);
        }
        
        return $this->generateToken($user['id'], $user['username']);
    }

    public function register($username, $password)
    {
        // Check if user already exists
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            if ($this->logger) {
                $this->logger->warning("Registration failed - username already exists: " . $username);
            }
            throw new \Exception('Username already exists');
        }
        
        // Insert new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $hashedPassword]);
        
        if ($this->logger) {
            $this->logger->info("New user registered: " . $username);
        }
        
        return true;
    }

    public function validateToken($token)
    {
        try {
            // Try using Firebase JWT if available
            if (class_exists('\\Firebase\\JWT\\JWT')) {
                $decoded = \Firebase\JWT\JWT::decode($token, $this->jwtSecret, ['HS256']);
            } else {
                // Fallback to our simple JWT implementation
                $decoded = JWT::decode($token, $this->jwtSecret, ['HS256']);
            }
            
            if ($this->logger) {
                $this->logger->info("Token validated successfully for user: " . ($decoded->username ?? 'unknown'));
            }
            
            return (array) $decoded;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning("Token validation failed: " . $e->getMessage());
            }
            return false;
        }
    }

    private function generateToken($userId, $username)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + $this->tokenExpiration;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'userId' => $userId,
            'username' => $username
        ];
        
        // Try using Firebase JWT if available
        if (class_exists('\\Firebase\\JWT\\JWT')) {
            return \Firebase\JWT\JWT::encode($payload, $this->jwtSecret);
        } else {
            // Fallback to our simple JWT implementation
            return JWT::encode($payload, $this->jwtSecret);
        }
    }
}
