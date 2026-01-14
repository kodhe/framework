<?php

namespace Kodhe\Framework\Exceptions\Auth;

use Kodhe\Framework\Exceptions\Http\ForbiddenException;

class AuthorizationException extends ForbiddenException
{
    protected string $errorCode = 'AUTHORIZATION_FAILED';
    protected int $httpStatusCode = 403;
    protected string $logLevel = 'info';

    /**
     * @var string|null Required permission
     */
    protected ?string $requiredPermission = null;

    /**
     * @var string|null Required role
     */
    protected ?string $requiredRole = null;

    /**
     * @var array User permissions
     */
    protected array $userPermissions = [];

    /**
     * @var array User roles
     */
    protected array $userRoles = [];

    public function __construct(string $message = '', string $requiredPermission = null, string $requiredRole = null)
    {
        parent::__construct($message);
        
        $this->requiredPermission = $requiredPermission;
        $this->requiredRole = $requiredRole;
        
        $data = [];
        if ($requiredPermission) {
            $data['required_permission'] = $requiredPermission;
        }
        if ($requiredRole) {
            $data['required_role'] = $requiredRole;
        }
        
        if (!empty($data)) {
            $this->withData($data);
        }
    }

    /**
     * Set user permissions
     *
     * @param array $permissions
     * @return self
     */
    public function withUserPermissions(array $permissions): self
    {
        $this->userPermissions = $permissions;
        $this->withData(['user_permissions' => $permissions]);
        return $this;
    }

    /**
     * Get user permissions
     *
     * @return array
     */
    public function getUserPermissions(): array
    {
        return $this->userPermissions;
    }

    /**
     * Set user roles
     *
     * @param array $roles
     * @return self
     */
    public function withUserRoles(array $roles): self
    {
        $this->userRoles = $roles;
        $this->withData(['user_roles' => $roles]);
        return $this;
    }

    /**
     * Get user roles
     *
     * @return array
     */
    public function getUserRoles(): array
    {
        return $this->userRoles;
    }

    /**
     * Get required permission
     *
     * @return string|null
     */
    public function getRequiredPermission(): ?string
    {
        return $this->requiredPermission;
    }

    /**
     * Get required role
     *
     * @return string|null
     */
    public function getRequiredRole(): ?string
    {
        return $this->requiredRole;
    }

    public static function roleRequired(string $role, array $userRoles = []): self
    {
        $exception = new self("Role required: {$role}", null, $role);
        if (!empty($userRoles)) {
            $exception->withUserRoles($userRoles);
        }
        return $exception;
    }

    public static function permissionRequired(string $permission, array $userPermissions = []): self
    {
        $exception = new self("Permission required: {$permission}", $permission);
        if (!empty($userPermissions)) {
            $exception->withUserPermissions($userPermissions);
        }
        return $exception;
    }

    public static function insufficientScope(string $scope): self
    {
        return new self("Insufficient scope: {$scope}", null, null);
    }

    public static function ownershipRequired(string $resourceType): self
    {
        return new self("Ownership required for {$resourceType}", null, null);
    }
}