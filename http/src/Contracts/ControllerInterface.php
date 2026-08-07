<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface ControllerInterface
 * 
 * Base controller interface for CodeIgniter 3 compatibility
 */
interface ControllerInterface
{
    /**
     * Execute a controller action
     */
    public function execute(string $method, array $params = []): ResponseInterface;

    /**
     * Set the request
     */
    public function setRequest(RequestInterface $request): self;

    /**
     * Get the request
     */
    public function getRequest(): RequestInterface;

    /**
     * Set the response
     */
    public function setResponse(ResponseInterface $response): self;

    /**
     * Get the response
     */
    public function getResponse(): ResponseInterface;

    /**
     * Load a model
     */
    public function loadModel(string $model, string $alias = '', bool $autoConnect = true);

    /**
     * Load a library
     */
    public function loadLibrary(string $library, array $params = [], ?string $objectName = null);

    /**
     * Load a helper
     */
    public function loadHelper(array|string $helper): void;

    /**
     * Load a view
     */
    public function loadView(string $view, array $data = [], bool $return = false);

    /**
     * Get validation instance
     */
    public function getValidation();

    /**
     * Set validation instance
     */
    public function setValidation($validation): self;

    /**
     * Get session instance
     */
    public function getSession();

    /**
     * Set session instance
     */
    public function setSession($session): self;

    /**
     * Get database instance
     */
    public function getDB();

    /**
     * Set database instance
     */
    public function setDB($db): self;

    /**
     * Get loader instance
     */
    public function getLoader();

    /**
     * Set loader instance
     */
    public function setLoader($loader): self;

    /**
     * Get config instance
     */
    public function getConfig();

    /**
     * Set config instance
     */
    public function setConfig($config): self;

    /**
     * Get input instance
     */
    public function getInput();

    /**
     * Set input instance
     */
    public function setInput($input): self;

    /**
     * Get output instance
     */
    public function getOutput();

    /**
     * Set output instance
     */
    public function setOutput($output): self;

    /**
     * Get URI instance
     */
    public function getURI();

    /**
     * Set URI instance
     */
    public function setURI($uri): self;

    /**
     * Get language instance
     */
    public function getLang();

    /**
     * Set language instance
     */
    public function setLang($lang): self;

    /**
     * Initialize controller
     */
    public function initialize(): void;

    /**
     * Get controller name
     */
    public function getControllerName(): string;

    /**
     * Get method name
     */
    public function getMethodName(): string;

    /**
     * Check if method exists
     */
    public function hasMethod(string $method): bool;
}
