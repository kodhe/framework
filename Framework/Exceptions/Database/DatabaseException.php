<?php

namespace Kodhe\Framework\Exceptions\Database;

use Kodhe\Framework\Exceptions\ApplicationException;

class DatabaseException extends ApplicationException
{
    protected string $errorCode = 'DATABASE_ERROR';
    protected int $httpStatusCode = 500;
    protected string $logLevel = 'error';

    /**
     * @var string|null SQL query that caused the error
     */
    protected ?string $sql = null;

    /**
     * @var array|null SQL query parameters
     */
    protected ?array $params = null;

    /**
     * @var string|null Database driver name
     */
    protected ?string $driver = null;

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Set SQL query
     *
     * @param string|null $sql
     * @return self
     */
    public function withSql(?string $sql): self
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * Get SQL query
     *
     * @return string|null
     */
    public function getSql(): ?string
    {
        return $this->sql;
    }

    /**
     * Set SQL parameters
     *
     * @param array|null $params
     * @return self
     */
    public function withParams(?array $params): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Get SQL parameters
     *
     * @return array|null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     * Set database driver
     *
     * @param string|null $driver
     * @return self
     */
    public function withDriver(?string $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get database driver
     *
     * @return string|null
     */
    public function getDriver(): ?string
    {
        return $this->driver;
    }

    public static function connectionFailed(string $connectionName = '', string $message = ''): self
    {
        $msg = 'Database connection failed';
        if ($connectionName) {
            $msg .= " for connection: {$connectionName}";
        }
        if ($message) {
            $msg .= " - {$message}";
        }
        
        return (new self($msg))
            ->withData([
                'connection' => $connectionName,
                'error' => $message
            ]);
    }

    public static function queryFailed(string $query = '', array $params = [], string $error = ''): self
    {
        $message = 'Database query failed';
        if ($error) {
            $message .= ": {$error}";
        }
        
        $exception = new self($message);
        
        $data = [];
        if ($query) {
            $data['query'] = $query;
        }
        if ($params) {
            $data['params'] = $params;
        }
        
        return $exception
            ->withSql($query)
            ->withParams($params)
            ->withData($data);
    }

    public static function transactionFailed(string $message = ''): self
    {
        $msg = 'Database transaction failed';
        if ($message) {
            $msg .= ": {$message}";
        }
        
        return new self($msg);
    }

    public static function constraintViolation(string $constraint = '', string $message = ''): self
    {
        $msg = 'Database constraint violation';
        if ($constraint) {
            $msg .= ": {$constraint}";
        }
        
        return (new self($msg))
            ->withData([
                'constraint' => $constraint,
                'error' => $message
            ])
            ->setHttpStatusCode(409); // Conflict
    }
}