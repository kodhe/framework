<?php

namespace Kodhe\Framework\Exceptions;

use Throwable;
use Kodhe\Framework\Exceptions\Middleware\ExceptionHandlerMiddleware;

class ExceptionHandler
{
    /**
     * @var bool Debug mode
     */
    protected bool $debug;

    /**
     * @var array Log levels for exceptions
     */
    protected array $logLevels = [
        Http\HttpException::class => 'warning',
        Http\ValidationException::class => 'info',
        Auth\AuthenticationException::class => 'info',
        Auth\AuthorizationException::class => 'info',
        \PDOException::class => 'error',
        \Error::class => 'error',
    ];
    
    /**
     * @var ExceptionHandlerMiddleware|null
     */
    protected ?ExceptionHandlerMiddleware $middleware = null;

    /**
     * ExceptionHandler constructor.
     *
     * @param bool $debug
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }
    
    /**
     * Get middleware instance
     *
     * @return ExceptionHandlerMiddleware
     */
    public function getMiddleware(): ExceptionHandlerMiddleware
    {
        if ($this->middleware === null) {
            $this->middleware = new ExceptionHandlerMiddleware($this->debug);
        }
        
        return $this->middleware;
    }
    
    /**
     * Set middleware instance
     *
     * @param ExceptionHandlerMiddleware $middleware
     * @return self
     */
    public function setMiddleware(ExceptionHandlerMiddleware $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * Handle exception
     *
     * @param Throwable $exception
     * @return array
     */
    public function handle(Throwable $exception): array
    {
        // Log the exception
        $this->logException($exception);

        // Prepare response
        $response = [
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'An error occurred',
            ],
            'status' => 500,
        ];

        // If it's our custom exception
        if ($exception instanceof BaseException) {
            $response['error']['code'] = $exception->getErrorCode();
            $response['error']['message'] = $exception->getMessage();
            $response['status'] = $exception->getHttpStatusCode();
            
            $data = $exception->getData();
            if (!empty($data)) {
                $response['error']['data'] = $data;
            }
        }

        // Add debug info if debug mode is enabled
        if ($this->debug) {
            $response['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
                'class' => get_class($exception),
            ];
        }

        return $response;
    }

    /**
     * Log exception
     *
     * @param Throwable $exception
     */
    protected function logException(Throwable $exception): void
    {
        $logLevel = $this->determineLogLevel($exception);
        $message = sprintf(
            '%s: %s in %s:%s',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        // Here you would integrate with your logging system
        error_log("[{$logLevel}] {$message}");
        
        // Log trace in debug mode
        if ($this->debug) {
            error_log('Stack trace: ' . $exception->getTraceAsString());
        }
    }

    /**
     * Determine log level for exception
     *
     * @param Throwable $exception
     * @return string
     */
    protected function determineLogLevel(Throwable $exception): string
    {
        foreach ($this->logLevels as $class => $level) {
            if ($exception instanceof $class) {
                return $level;
            }
        }

        return 'error';
    }

    /**
     * Register global exception handler
     */
    public function register(): void
    {
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
    }

    /**
     * Global exception handler
     *
     * @param Throwable $exception
     */
    public function handleException(Throwable $exception): void
    {
        $response = $this->handle($exception);
        
        http_response_code($response['status']);
        header('Content-Type: application/json');
        
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    /**
     * Convert errors to exceptions
     *
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     * @throws \ErrorException
     */
    public function handleError(int $severity, string $message, string $file, int $line): void
    {
        if (!(error_reporting() & $severity)) {
            return;
        }
        
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
}