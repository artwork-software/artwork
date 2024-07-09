<?php

namespace Tests\Feature\Artwork\Modules\Setup;

use Artwork\Modules\Setup\Database\ModifiesBaseData;
use Artwork\Modules\Setup\DataProvider\BaseDataProvider;
use Artwork\Modules\Setup\DataProvider\RoleAndPermissionDataProvider;
use Closure;
use Tests\TestCase;

class ModifyBaseDataTest extends TestCase
{
    public static function modifyDataProvider(): \Generator
    {
        yield 'add new permission and roles' => [
            new class extends BaseDataProvider {
                /** @return array <string, string> */
                public function getPermissions(): array
                {
                    $permissions = parent::getPermissions();
                    $permissions[] = [
                        'name' => 'testPermission',
                        'name_de' => "testPermissionDe",
                        'group' => 'Lel',
                        'tooltipText' => 'MyToolTip',
                    ];
                    return $permissions;
                }

                /** @return array <string, string> */
                public function getRoles(): array
                {
                    $roles = parent::getRoles();
                    $roles[] = [
                        'name' => 'TestRole',
                        'guard_name' => 'testRoleDe',
                        'name_de' => "TestRoleDe",
                        'tooltipText' => 'MyToolTip',
                    ];
                    return $roles;
                }
            },
            function (): void {
                $this->assertDatabaseMissing('permissions', [
                    'name' => 'testPermission',
                    'guard_name' => 'testPermission',
                    'name_de' => "testPermissionDe",
                    'group' => 'Lel',
                    'tooltipText' => 'MyToolTip',
                ]);
                $this->assertDatabaseMissing('roles', [
                    'name' => 'testRole',
                    'guard_name' => 'testRoleDe',
                    'name_de' => "testRoleDe",
                    'tooltipText' => 'MyToolTipForRole',
                ]);
            },
            function (): void {
                $this->assertDatabaseHas('permissions', [
                    'name' => 'testPermission',
                    'name_de' => "testPermissionDe",
                    'group' => 'Lel',
                    'tooltipText' => 'MyToolTip',
                ]);
                $this->assertDatabaseMissing('roles', [
                    'name' => 'testRole',
                    'guard_name' => 'testRoleDe',
                    'name_de' => "testRoleDe",
                    'tooltipText' => 'MyToolTipForRole',
                ]);
            }
        ];

        yield 'adds only new permission' => [
            new class extends BaseDataProvider {
                /** @return array <string, string> */
                public function getPermissions(): array
                {
                    $permissions = parent::getPermissions();
                    $permissions[] = [
                        'name' => 'testPermission',
                        'name_de' => "testPermissionDe",
                        'group' => 'Lel',
                        'tooltipText' => 'MyToolTip',
                    ];
                    $permissions[] = [
                        'name' => 'testPermission2',
                        'name_de' => "testPermissionDe2",
                        'group' => 'Lel2',
                        'tooltipText' => 'MyToolTip2',
                    ];
                    return $permissions;
                }
            },
            function (): void {
                $this->assertDatabaseMissing('permissions', [
                    'name' => 'testPermission',
                    'name_de' => "testPermissionDe",
                    'group' => 'Lel',
                    'tooltipText' => 'MyToolTip',
                ]);
                $this->assertDatabaseMissing('permissions', [
                    'name' => 'testPermission2',
                    'name_de' => "testPermissionDe2",
                    'group' => 'Lel2',
                    'tooltipText' => 'MyToolTip2',
                ]);
            },
            function (): void {
                $this->assertDatabaseHas('permissions', [
                    'name' => 'testPermission',
                    'name_de' => "testPermissionDe",
                    'group' => 'Lel',
                    'tooltipText' => 'MyToolTip',
                ]);
                $this->assertDatabaseHas('permissions', [
                    'name' => 'testPermission2',
                    'name_de' => "testPermissionDe2",
                    'group' => 'Lel2',
                    'tooltipText' => 'MyToolTip2',
                ]);
            }
        ];

        yield 'modifies existing data' => [
            new class extends BaseDataProvider {
                /** @return array <string, string> */
                public function getPermissions(): array
                {
                    $permissions = parent::getPermissions();
                    $firstEntry = $permissions[0];
                    $permissions[0] = [
                        'name' => $firstEntry['name'],
                        'name_de' => "testPermissionDe",
                        'group' => 'Lel',
                        'tooltipText' => 'MyToolTip',
                    ];
                    return $permissions;
                }

                /** @return array <string, string> */
                public function getOriginalPermissions(): array
                {
                    return parent::getPermissions();
                }

                /** @return array <string, string> */
                public function getOriginalRoles(): array
                {
                    return parent::getRoles();
                }

                /** @return array <string, string> */
                public function getRoles(): array
                {
                    $roles = parent::getRoles();
                    $firstEntry = $roles[0];
                    $roles[0] = [
                        'name' => $firstEntry['name'],
                        'name_de' => "testRoleDe",
                        'tooltipText' => 'MyToolTip',
                    ];

                    return $roles;
                }
            },
            function (): void {
                /** @var RoleAndPermissionDataProvider $provider */
                $provider = app()->get(RoleAndPermissionDataProvider::class);

                $this->assertDatabaseMissing('permissions', [
                    'name' => $provider->getPermissions()[0]['name'],
                    'name_de' => "testPermissionDe",
                    'group' => 'Lel',
                    'tooltipText' => 'MyToolTip',
                ]);
                $this->assertDatabaseMissing('roles', [
                    'name' => $provider->getRoles()[0]['name'],
                    'name_de' => "testRoleDe",
                    'tooltipText' => 'MyToolTip',
                ]);

                $this->assertDatabaseHas('permissions', [
                    'name' => $provider->getPermissions()[0]['name'],
                    'name_de' => $provider->getPermissions()[0]['name_de'],
                    'tooltipText' => $provider->getPermissions()[0]['tooltipText'],
                ]);
                $this->assertDatabaseHas('roles', [
                    'name' => $provider->getRoles()[0]['name'],
                    'name_de' => $provider->getRoles()[0]['name_de'],
                    'tooltipText' => $provider->getRoles()[0]['tooltipText'],
                ]);
            },
            function (): void {
                /** @var RoleAndPermissionDataProvider $provider */
                $provider = app()->get(RoleAndPermissionDataProvider::class);

                $this->assertDatabaseHas('permissions', [
                    'name' => $provider->getPermissions()[0]['name'],
                    'name_de' => "testPermissionDe",
                    'group' => 'Lel',
                    'tooltipText' => 'MyToolTip',
                ]);
                $this->assertDatabaseHas('roles', [
                    'name' => $provider->getRoles()[0]['name'],
                    'name_de' => "testRoleDe",
                    'tooltipText' => 'MyToolTip',
                ]);

                $this->assertDatabaseMissing('permissions', [
                    'name' => $provider->getOriginalPermissions()[0]['name'],
                    'name_de' => $provider->getOriginalPermissions()[0]['name_de'],
                    'group' => $provider->getOriginalPermissions()[0]['group'],
                    'tooltipText' => $provider->getOriginalPermissions()[0]['tooltipText'],
                ]);
                $this->assertDatabaseMissing('roles', [
                    'name_de' => $provider->getOriginalRoles()[0]['name_de'],
                    'tooltipText' => $provider->getOriginalRoles()[0]['tooltipText'],
                ]);
            }
        ];

        yield 'nothing to add' => [
            new class extends BaseDataProvider {
            },
            function (): void {
                $this->assertDatabaseCount(
                    'permissions',
                    count(app()->get(RoleAndPermissionDataProvider::class)->getPermissions())
                );
                $this->assertDatabaseCount(
                    'roles',
                    count(app()->get(RoleAndPermissionDataProvider::class)->getRoles())
                );
            },
            function (): void {
                $this->assertDatabaseCount(
                    'permissions',
                    count(app()->get(RoleAndPermissionDataProvider::class)->getPermissions())
                );
                $this->assertDatabaseCount(
                    'roles',
                    count(app()->get(RoleAndPermissionDataProvider::class)->getRoles())
                );
            },

            yield 'unknown column does not crash' => [
                new class extends BaseDataProvider {
                    /** @return array <string, string> */
                    public function getPermissions(): array
                    {
                        $permissions = parent::getPermissions();
                        $permissions[] = [
                            'name' => 'testPermission',
                            'name_de' => "testPermissionDe",
                            'group' => 'Lel',
                            'tooltipText' => 'MyToolTip',
                            'THIS_DOES_NOT_EXIST' => 'do not crash'
                        ];
                        $permissions[0] = [
                            'name' => $permissions[0]['name'],
                            'name_de' => "testPermissionDe",
                            'group' => 'Lel',
                            'tooltipText' => 'MyToolTip',
                           'NOT_EXISTENT' => 'do not crash'
                        ];

                        return $permissions;
                    }
                    /** @return array <string, string> */
                    public function getOriginalPermissions(): array
                    {
                        return parent::getPermissions();
                    }
                },
                function (): void {
                    $this->assertDatabaseCount(
                        'permissions',
                        count(app()->get(RoleAndPermissionDataProvider::class)->getPermissions())
                    );
                    $this->assertDatabaseCount(
                        'roles',
                        count(app()->get(RoleAndPermissionDataProvider::class)->getRoles())
                    );
                },
                function (): void {
                    /** @var RoleAndPermissionDataProvider $provider */
                    $provider = app()->get(RoleAndPermissionDataProvider::class);
                    $this->assertDatabaseHas('permissions', [
                        'name' => $provider->getOriginalPermissions()[0]['name'],
                        'name_de' => "testPermissionDe",
                        'group' => 'Lel',
                        'tooltipText' => 'MyToolTip',
                    ]);
                    $this->assertDatabaseCount(
                        'permissions',
                        count(app()->get(RoleAndPermissionDataProvider::class)->getPermissions())
                    );
                    $this->assertDatabaseCount(
                        'roles',
                        count(app()->get(RoleAndPermissionDataProvider::class)->getRoles())
                    );
                },

            ]
        ];
    }

    /** @dataProvider modifyDataProvider */
    public function testModifyBaseData(
        BaseDataProvider $provider,
        Closure $assertionsBefore,
        Closure $assertionsAfter
    ): void {
        $modifier = new class {
            use ModifiesBaseData;
        };

        $assertionsBefore->call($this);

        $this->app->bind(RoleAndPermissionDataProvider::class, fn() => $provider);
        $modifier->modifyBaseData();

        $assertionsAfter->call($this);
    }
}
