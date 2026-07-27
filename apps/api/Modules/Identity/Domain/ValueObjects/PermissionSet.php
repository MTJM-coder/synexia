<?php

namespace Modules\Identity\Domain\ValueObjects;

/**
 * Ensemble immuable de permissions résolues pour un employé, dans une boutique
 * donnée. C'est la seule structure que le reste de l'application doit utiliser
 * pour vérifier un droit — jamais une requête directe sur les tables
 * role_permissions / shop_employee_permissions.
 *
 * Immuable par design : une fois résolu, un PermissionSet ne change plus.
 * Toute évolution (grant/deny) passe par une Action qui invalide le cache
 * et force une nouvelle résolution.
 */
final class PermissionSet
{
    /** @var array<string, true> */
    private readonly array $permissions;

    /**
     * @param  string[]  $permissionNames
     */
    public function __construct(array $permissionNames)
    {
        // Stocké comme table associative pour des lookups en O(1).
        $this->permissions = array_fill_keys(
            array_map('strtolower', $permissionNames),
            true
        );
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function has(string $permission): bool
    {
        return isset($this->permissions[strtolower($permission)]);
    }

    /**
     * Vrai si AU MOINS une des permissions données est accordée.
     *
     * @param  string[]  $permissions
     */
    public function any(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->has($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vrai si TOUTES les permissions données sont accordées.
     *
     * @param  string[]  $permissions
     */
    public function all(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->has($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return array_keys($this->permissions);
    }

    public function count(): int
    {
        return count($this->permissions);
    }
}
