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
                try {
                    $json = json_encode($this->data, JSON_PRETTY_PRINT);
                    if ($json === false) {
                        $error = json_last_error_msg();
                        error_log("JSON encode error: " . $error);
                        echo json_encode(['error' => 'Failed to encode response data: ' . $error]);
                    } else {
                        echo $json;
                    }
                } catch (\Exception $e) {
                    error_log("Exception encoding JSON: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                    echo json_encode(['error' => 'Internal server error']);
                }
            }
        }
        exit;
    }

    /**
     * Set a header value
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return void
     */
    public function setHeader(string $name, $value): void
    {
        $this->headers[$name] = $value;
    }
}
