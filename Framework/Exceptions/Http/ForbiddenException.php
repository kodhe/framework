<?php

namespace Kodhe\Framework\Exceptions\Http;

class ForbiddenException extends HttpException
{
    protected string $errorCode = 'FORBIDDEN';
    protected int $httpStatusCode = 403;
    protected string $logLevel = 'info';

    public function __construct(string $message = 'Access forbidden')
    {
        parent::__construct($message);
    }

    public static function insufficientPermissions(string $permission = '', array $requiredPermissions = []): self
    {
        $message = 'Insufficient permissions';
        $data = [];
        
        if ($permission) {
            $message .= ": {$permission}";
            $data['missing_permission'] = $permission;
        }
        
        if (!empty($requiredPermissions)) {
            $data['required_permissions'] = $requiredPermissions;
        }
        
        return (new self($message))->withData($data);
    }

    public static function roleRequired(string $role, array $userRoles = []): self
    {
        $message = "Role required: {$role}";
        $data = ['required_role' => $role];
        
        if (!empty($userRoles)) {
            $data['user_roles'] = $userRoles;
        }
        
        return (new self($message))->withData($data);
    }

    public static function ipBlocked(string $ip): self
    {
        return (new self("Access blocked for IP: {$ip}"))
            ->withData(['blocked_ip' => $ip])
            ->setLogLevel('warning');
    }

    public static function rateLimitExceeded(int $limit, int $retryAfter = 60): self
    {
        return (new self('Rate limit exceeded'))
            ->withHeaders(['Retry-After' => $retryAfter])
            ->withData([
                'limit' => $limit,
                'retry_after' => $retryAfter
            ]);
    }
}