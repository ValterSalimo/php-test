<?php

namespace App\Core;

use App\Service\LogService;

class ErrorHandler
{
    private $logService;
    private $debug;

    public function __construct(LogService $logService, bool $debug = false)
    {
        $this->logService = $logService;
        $this->debug = $debug;
    }

    public function register()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError($level, $message, $file = '', $line = 0)
    {
        $this->logService->error("PHP Error: $message", [
            'level' => $level,
            'file' => $file,
            'line' => $line
        ]);

        return false;
    }

    public function handleException(\Throwable $exception)
    {
        $this->logService->error("Uncaught Exception: " . $exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        $statusCode = 500;
        
        if ($exception instanceof \App\Exception\NotFoundException) {
            $statusCode = 404;
        } elseif ($exception instanceof \App\Exception\ValidationException) {
            $statusCode = 400;
        } elseif ($exception instanceof \App\Exception\AuthenticationException) {
            $statusCode = 401;
        } elseif ($exception instanceof \App\Exception\AuthorizationException) {
            $statusCode = 403;
        }

        $response = new Response(
            ['error' => $this->debug ? $exception->getMessage() : 'An error occurred'],
            $statusCode
        );
        
        $response->send();
    }

    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
            $this->handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }
}
