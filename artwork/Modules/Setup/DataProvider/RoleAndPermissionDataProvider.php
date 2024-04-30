<?php

namespace Artwork\Modules\Setup\DataProvider;

interface RoleAndPermissionDataProvider
{
    public function getPermissions(): array;

    public function getRoles(): array;

    public function getExcludedPermissionColumns(): array;

    public function getExcludedRoleColumns(): array;
}
