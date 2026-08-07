<?php

declare(strict_types=1);

namespace Kodhe\Framework\Http\Controllers;

use Kodhe\Framework\Http\Contracts\RequestInterface;
use Kodhe\Framework\Http\Contracts\ResponseInterface;

/**
 * Controller - Standard controller class extending BaseController
 * 
 * Compatible with CodeIgniter 3 while providing modern PSR-based architecture
 */
class Controller extends BaseController
{
    /**
     * Execute an action
     *
     * @param string $method
     * @param array $parameters
     * @return ResponseInterface|mixed
     */
    public function execute(string $method, array $parameters = [])
    {
        return $this->callAction($method, $parameters);
    }

    /**
     * Handle options request for CORS preflight
     *
     * @return ResponseInterface
     */
    public function options(): ResponseInterface
    {
        return $this->response->withStatus(204)
            ->withHeader('Allow', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    /**
     * Get the middleware that should be applied to this controller's actions
     *
     * @return array
     */
    public function getMiddlewareForAction(string $action): array
    {
        $middleware = [];

        foreach ($this->middleware as $mwConfig) {
            $mw = $mwConfig['middleware'];
            $options = $mwConfig['options'] ?? [];

            // Check if middleware applies to this action
            if (isset($options['only'])) {
                if (in_array($action, (array) $options['only'])) {
                    $middleware[] = $mw;
                }
            } elseif (isset($options['except'])) {
                if (!in_array($action, (array) $options['except'])) {
                    $middleware[] = $mw;
                }
            } else {
                $middleware[] = $mw;
            }
        }

        return $middleware;
    }
}
