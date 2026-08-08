<?php

declare(strict_types=0);

namespace Kodhe\Framework\Xmlrpcs\ValueObjects;

/**
 * Value object for XML-RPC response
 */
class Response
{
    /**
     * Response value
     *
     * @var mixed
     */
    protected $value;

    /**
     * Fault code (0 = no fault)
     *
     * @var int
     */
    protected int $faultCode = 0;

    /**
     * Fault string
     *
     * @var string
     */
    protected string $faultString = '';

    /**
     * Debug message
     *
     * @var string
     */
    protected string $debugMsg = '';

    /**
     * Encoding
     *
     * @var string
     */
    protected string $encoding = 'UTF-8';

    /**
     * Constructor
     *
     * @param mixed $value
     * @param int $faultCode
     * @param string $faultString
     * @param string $encoding
     */
    public function __construct(
        $value = null,
        int $faultCode = 0,
        string $faultString = '',
        string $encoding = 'UTF-8'
    ) {
        $this->value = $value;
        $this->faultCode = $faultCode;
        $this->faultString = $faultString;
        $this->encoding = $encoding;
    }

    /**
     * Check if this is a fault response
     *
     * @return bool
     */
    public function isFault(): bool
    {
        return $this->faultCode !== 0;
    }

    /**
     * Get fault code
     *
     * @return int
     */
    public function getFaultCode(): int
    {
        return $this->faultCode;
    }

    /**
     * Get fault string
     *
     * @return string
     */
    public function getFaultString(): string
    {
        return $this->faultString;
    }

    /**
     * Get value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set debug message
     *
     * @param string $msg
     * @return void
     */
    public function setDebugMsg(string $msg): void
    {
        $this->debugMsg = $msg;
    }

    /**
     * Get debug message
     *
     * @return string
     */
    public function getDebugMsg(): string
    {
        return $this->debugMsg;
    }

    /**
     * Prepare response as XML string
     *
     * @return string
     */
    public function prepareResponse(): string
    {
        if ($this->isFault()) {
            return $this->generateFaultXml();
        }

        return $this->generateSuccessXml();
    }

    /**
     * Generate fault XML
     *
     * @return string
     */
    protected function generateFaultXml(): string
    {
        $struct = [
            'faultCode' => $this->faultCode,
            'faultString' => $this->faultString
        ];

        return '<methodResponse>'.
               '<fault>'.
               '<value><struct>'.
               '<member><name>faultCode</name><value><int>'.$this->faultCode.'</int></value></member>'.
               '<member><name>faultString</name><value><string>'.htmlspecialchars($this->faultString).'</string></value></member>'.
               '</struct></value>'.
               '</fault>'.
               '</methodResponse>';
    }

    /**
     * Generate success XML
     *
     * @return string
     */
    protected function generateSuccessXml(): string
    {
        return '<methodResponse>'.
               '<params>'.
               '<param>'.
               '<value>'.$this->value.'</value>'.
               '</param>'.
               '</params>'.
               '</methodResponse>';
    }
}
