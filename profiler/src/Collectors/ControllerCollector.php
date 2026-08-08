<?php

declare(strict_types=1);

namespace Kodhe\Framework\Profiler\Collectors;

use Kodhe\Framework\Profiler\Contracts\CollectorInterface;

/**
 * Controller Info Collector
 * 
 * Collects controller and method information
 */
class ControllerCollector implements CollectorInterface
{
    protected object $ci;
    protected ?string $controllerClass = null;
    protected ?string $controllerMethod = null;

    public function setDependencies(object $ci): void
    {
        $this->ci = $ci;
    }

    public function collect(): array
    {
        if ($this->controllerClass !== null) {
            return [
                'controller' => $this->controllerClass,
                'method' => $this->controllerMethod
            ];
        }

        $this->controllerClass = $this->ci->router->class ?? '';
        $this->controllerMethod = $this->ci->router->method ?? '';

        return [
            'controller' => $this->controllerClass,
            'method' => $this->controllerMethod,
            'full_path' => $this->controllerClass . '/' . $this->controllerMethod
        ];
    }

    public function hasData(): bool
    {
        // Always has data (even if empty)
        return true;
    }

    public function getSectionName(): string
    {
        return 'controller_info';
    }

    public function getControllerInfo(): array
    {
        if ($this->controllerClass === null) {
            $this->collect();
        }
        return [
            'controller' => $this->controllerClass,
            'method' => $this->controllerMethod
        ];
    }
}
