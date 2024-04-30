<?php

namespace Artwork\Modules\Setup\DataProvider;

interface RoleAndPermissionDataProvider
{
    /**
     * @return array<mixed, mixed>
     */
    public function getPermissions(): array;

    /**
     * @return array<mixed, mixed>
     */
    public function getRoles(): array;

    /**
     * @return array<mixed, mixed>
     */
    public function getExcludedPermissionColumns(): array;

    /**
     * @return array<mixed, mixed>
     */
    public function getExcludedRoleColumns(): array;
}
