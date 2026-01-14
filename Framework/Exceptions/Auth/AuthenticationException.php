<?php

namespace Kodhe\Framework\Exceptions\Auth;

use Kodhe\Framework\Exceptions\Http\UnauthorizedException;

class AuthenticationException extends UnauthorizedException
{
    protected string $errorCode = 'AUTHENTICATION_FAILED';
    protected int $httpStatusCode = 401;
    protected string $logLevel = 'warning';

    /**
     * @var string|null Authentication method
     */
    protected ?string $method = null;

    /**
     * @var string|null User identifier
     */
    protected ?string $userIdentifier = null;

    public function __construct(string $message = '', string $method = null, string $userIdentifier = null)
    {
        parent::__construct($message);
        
        $this->method = $method;
        $this->userIdentifier = $userIdentifier;
        
        $data = [];
        if ($method) {
            $data['method'] = $method;
        }
        if ($userIdentifier) {
            $data['user_identifier'] = $userIdentifier;
        }
        
        if (!empty($data)) {
            $this->withData($data);
        }
    }

    /**
     * Get authentication method
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get user identifier
     *
     * @return string|null
     */
    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public static function invalidCredentials(string $method = '', string $identifier = ''): self
    {
        return new self('Invalid credentials', $method, $identifier);
    }

    public static function tokenExpired(string $tokenType = 'access'): self
    {
        return (new self("{$tokenType} token expired"))
            ->withData(['token_type' => $tokenType, 'reason' => 'expired']);
    }

    public static function tokenInvalid(string $tokenType = 'access', string $reason = ''): self
    {
        $message = "Invalid {$tokenType} token";
        if ($reason) {
            $message .= ": {$reason}";
        }
        
        return (new self($message))
            ->withData(['token_type' => $tokenType, 'reason' => $reason]);
    }

    public static function accountLocked(string $identifier = '', int $duration = 0): self
    {
        $message = 'Account is locked';
        $data = ['reason' => 'account_locked'];
        
        if ($identifier) {
            $data['user_identifier'] = $identifier;
        }
        if ($duration > 0) {
            $data['lock_duration'] = $duration;
        }
        
        return (new self($message))->withData($data);
    }

    public static function accountInactive(string $identifier = ''): self
    {
        $message = 'Account is inactive';
        $data = ['reason' => 'account_inactive'];
        
        if ($identifier) {
            $data['user_identifier'] = $identifier;
        }
        
        return (new self($message))->withData($data);
    }
}