<?php

namespace Kodhe\Framework\Exceptions;

class FileSystemException extends ApplicationException
{
    protected string $errorCode = 'FILESYSTEM_ERROR';
    protected int $httpStatusCode = 500;
    protected string $logLevel = 'error';

    /**
     * @var string|null File path
     */
    protected ?string $path = null;

    /**
     * @var string|null Operation that failed
     */
    protected ?string $operation = null;

    public function __construct(string $message = '', string $path = null, string $operation = null)
    {
        parent::__construct($message);
        
        $this->path = $path;
        $this->operation = $operation;
        
        $data = [];
        if ($path) {
            $data['path'] = $path;
        }
        if ($operation) {
            $data['operation'] = $operation;
        }
        
        if (!empty($data)) {
            $this->withData($data);
        }
    }

    /**
     * Get file path
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Get operation
     *
     * @return string|null
     */
    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public static function notFound(string $path, string $operation = ''): self
    {
        return new self("File not found: {$path}", $path, $operation);
    }

    public static function notReadable(string $path, string $operation = ''): self
    {
        return new self("File not readable: {$path}", $path, $operation);
    }

    public static function notWritable(string $path, string $operation = ''): self
    {
        return new self("File not writable: {$path}", $path, $operation);
    }

    public static function permissionDenied(string $path, string $operation = ''): self
    {
        return new self("Permission denied: {$path}", $path, $operation);
    }

    public static function diskFull(string $path, string $operation = ''): self
    {
        return new self("Disk full: {$path}", $path, $operation);
    }

    public static function uploadFailed(string $fileName, string $error = ''): self
    {
        $message = "File upload failed: {$fileName}";
        if ($error) {
            $message .= " - {$error}";
        }
        
        return new self($message, $fileName, 'upload');
    }

    public static function sizeExceeded(string $fileName, int $maxSize): self
    {
        return new self("File size exceeded: {$fileName} (max: {$maxSize} bytes)", $fileName, 'upload')
            ->withData(['max_size' => $maxSize]);
    }

    public static function invalidType(string $fileName, array $allowedTypes): self
    {
        return new self("Invalid file type: {$fileName}", $fileName, 'upload')
            ->withData(['allowed_types' => $allowedTypes]);
    }
}