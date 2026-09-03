<?php

namespace Artwork\Modules\Setup\DataProvider;

use Artwork\Modules\Permission\Catalog\PermissionCatalog;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Permission\Services\ShiftSettingsPermissionService;

class BaseDataProvider implements RoleAndPermissionDataProvider
{
    /**
     * @return string []
     */
    /**
     * Rechte kommen aus dem Rechte-Katalog (artwork/Modules/Permission/Catalog) — eine Quelle für
     * Seed, Update-Command, Rechteseite, Presets und Einladung.
     */
    public function getPermissions(): array
    {
        return app(PermissionCatalog::class)->seedRows();
    }

    public function getRoles(): array
    {
        return [
            [
                'name' => RoleEnum::ARTWORK_ADMIN->value,
                'translation_key' => "artwork admin",
                'tooltipKey' => 'The admin has all permissions in the system and ' .
                 'can therefore see and edit everything.',
            ],
        ];
    }

    /**
     * @return string []
     */
    public function getExcludedPermissionColumns(): array
    {
        return ['id', 'guard_name', 'created_at', 'updated_at'];
    }

    /**
     * @return string []
     */
    public function getExcludedRoleColumns(): array
    {
        return ['id', 'guard_name', 'created_at', 'updated_at'];
    }
}
