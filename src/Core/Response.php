<?php

namespace App\Core;

class Response
{
    private $data;
    private $statusCode;
    private $headers;
    private $headersSent = false;

    public function __construct($data, int $statusCode = 200, array $headers = [])
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = array_merge([
            'Content-Type' => 'application/json'
        ], $headers);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function send()
    {
        // Only try to set headers if they haven't been sent yet
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
            $this->headersSent = true;
        } else {
            // Log a warning if headers were already sent
            error_log("Warning: Headers already sent, unable to set response headers");
        }
        
        if ($this->headers['Content-Type'] === 'text/html') {
            // If HTML type, output data directly
            echo $this->data;
        } else {
            // Otherwise, JSON encode (handle null case)
            if ($this->data === null) {
                echo json_encode([]);
            } else {
                echo json_encode($this->data, JSON_PRETTY_PRINT);
            }
        }
        exit;
    }
}
