<?php

declare(strict_types=1);

namespace Kodhe\Framework\Exceptions;

/**
 * Thrown when load_class() cannot locate the requested class file.
 *
 * Replaces the legacy hard-fail behavior (echo + exit(5) / EXIT_UNK_CLASS)
 * so callers can catch and recover instead of the process dying silently.
 */
class ClassLoadingException extends ApplicationException
{
    protected string $errorCode = 'CLASS_LOAD_ERROR';
    protected int $httpStatusCode = 503;
    protected string $logLevel = 'critical';

    /**
     * The requested (short) class name that could not be located.
     */
    private string $requestedClass;

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null, string $requestedClass = '')
    {
        parent::__construct($message, $code, $previous);
        $this->requestedClass = $requestedClass;
    }

    /**
     * Factory for the classic "Unable to locate the specified class" failure.
     */
    public static function unableToLocate(string $class, string $directory = ''): self
    {
        $message = 'Unable to locate the specified class: ' . $class . '.php';

        return (new self($message, 0, null, $class))
            ->withData([
                'class'     => $class,
                'directory' => $directory,
            ]);
    }

    /**
     * Factory for namespaced classes that could not be autoloaded.
     */
    public static function unableToAutoload(string $namespacedClass): self
    {
        return (new self('Unable to autoload the specified class: ' . $namespacedClass, 0, null, $namespacedClass))
            ->withData(['class' => $namespacedClass]);
    }

    public function getRequestedClass(): string
    {
        return $this->requestedClass;
    }
}
