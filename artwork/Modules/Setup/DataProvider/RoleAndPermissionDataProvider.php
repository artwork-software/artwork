<?php

namespace Artwork\Modules\Setup\DataProvider;

interface RoleAndPermissionDataProvider
{
    public function getPermissions(): array;

    public function getRoles(): array;
}
