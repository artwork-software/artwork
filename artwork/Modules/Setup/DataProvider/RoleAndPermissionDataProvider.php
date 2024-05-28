<?php

namespace Artwork\Modules\Setup\DataProvider;

interface RoleAndPermissionDataProvider
{
    /**
     * @return string []
     */
    public function getPermissions(): array;

    /**
     * @return string []
     */
    public function getRoles(): array;

    /**
     * @return string []
     */
    public function getExcludedPermissionColumns(): array;

    /**
     * @return string []
     */
    public function getExcludedRoleColumns(): array;
}
