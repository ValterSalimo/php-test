<?php

namespace App\Service;

class LogService
{
    private $logFile;
    
    public function __construct($logFile = '/server/http/logs/app.log')
    {
        $this->logFile = $logFile;
        $this->ensureLogDirectoryExists();
    }
    
    private function ensureLogDirectoryExists()
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
    
    public function emergency($message, $context = [])
    {
        $this->log('EMERGENCY', $message, $context);
    }
    
    public function alert($message, $context = [])
    {
        $this->log('ALERT', $message, $context);
    }
    
    public function critical($message, $context = [])
    {
        $this->log('CRITICAL', $message, $context);
    }
    
    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }
    
    public function warning($message, $context = [])
    {
        $this->log('WARNING', $message, $context);
    }
    
    public function notice($message, $context = [])
    {
        $this->log('NOTICE', $message, $context);
    }
    
    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }
    
    public function debug($message, $context = [])
    {
        $this->log('DEBUG', $message, $context);
    }
    
    public function log($level, $message, $context = [])
    {
        $date = new \DateTime();
        $timestamp = $date->format('Y-m-d H:i:s');
        
        $logMessage = "[$timestamp] [$level]: $message";
        
        // Add context if provided
        if (!empty($context)) {
            $logMessage .= " " . json_encode($context);
        }
        
        $logMessage .= PHP_EOL;
        
        // Just silently continue if we can't log
        try {
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        } catch (\Exception $e) {
            // Do nothing
        }
    }
}
