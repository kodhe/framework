<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpcs\ValueObjects;

/**
 * Value object for XML-RPC request
 */
class Request
{
    /**
     * Method name
     *
     * @var string
     */
    protected string $methodName;

    /**
     * Parameters
     *
     * @var array
     */
    protected array $params;

    /**
     * Raw XML data
     *
     * @var string|null
     */
    protected ?string $rawXml;

    /**
     * Constructor
     *
     * @param string $methodName
     * @param array $params
     * @param string|null $rawXml
     */
    public function __construct(string $methodName, array $params = [], ?string $rawXml = null)
    {
        $this->methodName = $methodName;
        $this->params = $params;
        $this->rawXml = $rawXml;
    }

    /**
     * Get method name
     *
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get raw XML
     *
     * @return string|null
     */
    public function getRawXml(): ?string
    {
        return $this->rawXml;
    }

    /**
     * Check if request has parameters
     *
     * @return bool
     */
    public function hasParams(): bool
    {
        return count($this->params) > 0;
    }

    /**
     * Get parameter count
     *
     * @return int
     */
    public function getParamCount(): int
    {
        return count($this->params);
    }
}
