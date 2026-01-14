<?php

namespace Kodhe\Framework\Exceptions\Middleware;

use Kodhe\Framework\Middleware\Middleware;
use Kodhe\Framework\Http\Request;
use Kodhe\Framework\Http\Response;
use Kodhe\Framework\Exceptions\BaseException;
use Kodhe\Framework\Exceptions\Http\HttpException;
use Throwable;

class ExceptionHandlerMiddleware extends Middleware
{
    /**
     * @var bool Debug mode
     */
    protected bool $debug;
    
    /**
     * @var array Custom exception handlers
     */
    protected array $handlers = [];
    
    /**
     * Constructor
     *
     * @param bool $debug
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
        
        // Register default exception handlers
        $this->registerDefaultHandlers();
    }
    
    /**
     * Before hook - ini akan dipanggil sebelum request diproses
     */
    public function before($request, $response, $arguments = null)
    {
        // Nothing to do before processing
        return null;
    }
    
    /**
     * After hook - ini akan menangkap exception jika terjadi
     */
    public function after($request, $response, $arguments = null, $controllerResult = null)
    {
        // Exception handling sudah dilakukan di handle() method
        return $controllerResult;
    }
    
    /**
     * Handle the request with exception handling
     */
    public function handle(Request $request, Response $response, callable $next, array $params = [])
    {
        try {
            log_message('debug', 'ExceptionHandlerMiddleware::handle() - processing request');
            return parent::handle($request, $response, $next, $params);
            
        } catch (Throwable $e) {
            log_message('error', 'ExceptionHandlerMiddleware caught exception: ' . $e->getMessage());
            return $this->handleException($e, $request, $response);
        }
    }
    
    /**
     * Handle exception
     */
    protected function handleException(Throwable $exception, Request $request, Response $response): Response
    {
        // Find appropriate handler
        $handler = $this->findHandler($exception);
        
        // Execute handler
        return $handler($exception, $request, $response);
    }
    
    /**
     * Register default exception handlers
     */
    protected function registerDefaultHandlers(): void
    {
        $this->handlers = [
            // HTTP Exceptions
            Http\BadRequestException::class => fn($e, $req, $res) => $this->handleHttpException($e, $res),
            Http\NotFoundException::class => fn($e, $req, $res) => $this->handleHttpException($e, $res),
            Http\UnauthorizedException::class => fn($e, $req, $res) => $this->handleHttpException($e, $res),
            Http\ForbiddenException::class => fn($e, $req, $res) => $this->handleHttpException($e, $res),
            Http\MethodNotAllowedException::class => fn($e, $req, $res) => $this->handleHttpException($e, $res),
            Http\ValidationException::class => fn($e, $req, $res) => $this->handleValidationException($e, $res),
            
            // Auth Exceptions
            Auth\AuthenticationException::class => fn($e, $req, $res) => $this->handleHttpException($e, $res),
            Auth\AuthorizationException::class => fn($e, $req, $res) => $this->handleHttpException($e, $res),
            
            // Database Exceptions
            Database\DatabaseException::class => fn($e, $req, $res) => $this->handleDatabaseException($e, $res),
            Database\RecordNotFoundException::class => fn($e, $req, $res) => $this->handleRecordNotFound($e, $res),
            Database\DuplicateEntryException::class => fn($e, $req, $res) => $this->handleDuplicateEntry($e, $res),
            
            // Framework Exceptions
            ConfigurationException::class => fn($e, $req, $res) => $this->handleConfigurationException($e, $res),
            ContainerException::class => fn($e, $req, $res) => $this->handleContainerException($e, $res),
            
            // Default
            BaseException::class => fn($e, $req, $res) => $this->handleBaseException($e, $res),
        ];
    }
    
    /**
     * Find appropriate handler for exception
     */
    protected function findHandler(Throwable $exception): callable
    {
        // Check exact class match
        $exceptionClass = get_class($exception);
        if (isset($this->handlers[$exceptionClass])) {
            return $this->handlers[$exceptionClass];
        }
        
        // Check parent classes
        foreach ($this->handlers as $class => $handler) {
            if ($exception instanceof $class) {
                return $handler;
            }
        }
        
        // Default handler
        return fn($e, $req, $res) => $this->handleGenericException($e, $res);
    }
    
    /**
     * Handle HTTP exceptions
     */
    protected function handleHttpException(HttpException $exception, Response $response): Response
    {
        $data = $this->buildErrorData($exception);
        
        $response->setStatus($exception->getHttpStatusCode());
        $response->json($data);
        
        // Add headers from exception
        $headers = $exception->getHeaders();
        foreach ($headers as $name => $value) {
            $response->setHeader($name, $value);
        }
        
        return $response;
    }
    
    /**
     * Handle ValidationException
     */
    protected function handleValidationException(Http\ValidationException $exception, Response $response): Response
    {
        $data = $this->buildErrorData($exception);
        $data['error']['errors'] = $exception->getErrors();
        
        $response->setStatus($exception->getHttpStatusCode());
        $response->json($data);
        
        return $response;
    }
    
    /**
     * Handle RecordNotFoundException
     */
    protected function handleRecordNotFound(Database\RecordNotFoundException $exception, Response $response): Response
    {
        $data = $this->buildErrorData($exception);
        
        $response->setStatus(404);
        $response->json($data);
        
        return $response;
    }
    
    /**
     * Handle DuplicateEntryException
     */
    protected function handleDuplicateEntry(Database\DuplicateEntryException $exception, Response $response): Response
    {
        $data = $this->buildErrorData($exception);
        
        $response->setStatus(409);
        $response->json($data);
        
        return $response;
    }
    
    /**
     * Handle DatabaseException
     */
    protected function handleDatabaseException(Database\DatabaseException $exception, Response $response): Response
    {
        $data = $this->buildErrorData($exception);
        
        // Don't expose database errors in production
        if (!$this->debug) {
            $data['error']['message'] = 'Database error occurred';
            unset($data['error']['data']);
        }
        
        $response->setStatus(500);
        $response->json($data);
        
        return $response;
    }
    
    /**
     * Handle ConfigurationException
     */
    protected function handleConfigurationException(ConfigurationException $exception, Response $response): Response
    {
        $data = $this->buildErrorData($exception);
        
        $response->setStatus(500);
        $response->json($data);
        
        return $response;
    }
    
    /**
     * Handle ContainerException
     */
    protected function handleContainerException(ContainerException $exception, Response $response): Response
    {
        $data = $this->buildErrorData($exception);
        
        $response->setStatus(500);
        $response->json($data);
        
        return $response;
    }
    
    /**
     * Handle BaseException
     */
    protected function handleBaseException(BaseException $exception, Response $response): Response
    {
        $data = $this->buildErrorData($exception);
        
        $response->setStatus($exception->getHttpStatusCode());
        $response->json($data);
        
        return $response;
    }
    
    /**
     * Handle generic exceptions
     */
    protected function handleGenericException(Throwable $exception, Response $response): Response
    {
        $data = [
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => $this->debug ? $exception->getMessage() : 'Internal Server Error',
            ]
        ];
        
        if ($this->debug) {
            $data['debug'] = [
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }
        
        $response->setStatus(500);
        $response->json($data);
        
        return $response;
    }
    
    /**
     * Build error data from exception
     */
    protected function buildErrorData(BaseException $exception): array
    {
        $data = [
            'error' => [
                'code' => $exception->getErrorCode(),
                'message' => $exception->getMessage(),
            ]
        ];
        
        // Add exception data if available
        $exceptionData = $exception->getData();
        if (!empty($exceptionData)) {
            $data['error']['data'] = $exceptionData;
        }
        
        // Add debug info if enabled
        if ($this->debug) {
            $data['debug'] = [
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
            
            // Add previous exception if exists
            $previous = $exception->getPrevious();
            if ($previous) {
                $data['debug']['previous'] = [
                    'type' => get_class($previous),
                    'message' => $previous->getMessage(),
                    'file' => $previous->getFile(),
                    'line' => $previous->getLine(),
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Set debug mode
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }
    
    /**
     * Get debug mode
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }
}