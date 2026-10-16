<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * InventoryUserFilterShareService teilt rooms/users/manufacturers für Filter und Auswahllisten
 * (Artikelplanung, Materialausgaben intern/extern, Material-Sets, Projekt-Tabs). Das Frontend liest
 * davon nur id + Name; früher kamen Room::$with (admins/creator) und die User-/CrmContact-Appends
 * (profile_photo_url, full_name, type, formated_work_time_balance) mit.
 */
final class InventoryFilterSharePayloadTest extends FeatureTestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function pagesSharingFilterData(): array
    {
        return [
            'Material-Sets' => ['material-sets.index'],
            'Materialausgaben intern' => ['issue-of-material.index'],
            'Materialausgaben extern' => ['extern-issue-of-material.index'],
            'Artikelplanung' => ['inventory-management.article.planning'],
        ];
    }

    #[Test]
    #[DataProvider('pagesSharingFilterData')]
    public function shared_filter_lists_carry_only_the_fields_the_frontend_reads(string $routeName): void
    {
        $this->actingAsAdmin();
        $room = Room::factory()->create(['name' => 'AAA Probebühne']);
        $room->users()->attach(User::factory()->create()->id, ['is_admin' => true, 'can_request' => false]);
        CrmContact::factory()->create([
            'crm_contact_type_id' => $this->manufacturerType()->id,
            'display_name' => 'AAA Hersteller',
        ]);

        $this->get(route($routeName))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $props = $page->toArray()['props'];

                $this->assertNotEmpty($props['users']);
                foreach ($props['users'] as $user) {
                    $this->assertSame(['id', 'first_name', 'last_name'], array_keys($user));
                    $this->assertIsInt($user['id']);
                }
                $this->assertNotEmpty($props['rooms']);
                foreach ($props['rooms'] as $room) {
                    $this->assertSame(['id', 'name'], array_keys($room));
                    $this->assertIsInt($room['id']);
                }
                $this->assertNotEmpty($props['manufacturers']);
                foreach ($props['manufacturers'] as $manufacturer) {
                    $this->assertSame(['id', 'name'], array_keys($manufacturer));
                    $this->assertIsInt($manufacturer['id']);
                }
            });
    }

    #[Test]
    public function shared_filter_lists_keep_their_content_order_and_scopes(): void
    {
        $admin = $this->actingAsAdmin();
        $user = User::factory()->create(['first_name' => 'Aaron', 'last_name' => 'Zeiss']);

        $room = Room::factory()->create(['name' => 'AAA Probebühne']);
        $trashedRoom = Room::factory()->create(['name' => 'AAA Abgerissen']);
        $trashedRoom->delete();

        $manufacturerType = $this->manufacturerType();
        $manufacturerB = CrmContact::factory()->create([
            'crm_contact_type_id' => $manufacturerType->id,
            'display_name' => 'AAB Hersteller',
        ]);
        $manufacturerA = CrmContact::factory()->create([
            'crm_contact_type_id' => $manufacturerType->id,
            'display_name' => 'AAA Hersteller',
        ]);
        $trashedManufacturer = CrmContact::factory()->create([
            'crm_contact_type_id' => $manufacturerType->id,
            'display_name' => 'AAA Insolvent',
        ]);
        $trashedManufacturer->delete();
        $otherContact = CrmContact::factory()->create(['display_name' => 'AAA Kein Hersteller']);

        $this->get(route('material-sets.index'))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (
                $admin,
                $user,
                $room,
                $trashedRoom,
                $manufacturerA,
                $manufacturerB,
                $trashedManufacturer,
                $otherContact,
            ): void {
                $props = $page->toArray()['props'];

                $users = collect($props['users']);
                $this->assertSame(User::query()->count(), $users->count());
                $this->assertSame(
                    ['id' => $user->id, 'first_name' => 'Aaron', 'last_name' => 'Zeiss'],
                    $users->firstWhere('id', $user->id)
                );
                $this->assertNotNull($users->firstWhere('id', $admin->id));
                $this->assertSame(
                    User::query()->orderBy('first_name')->pluck('first_name')->all(),
                    $users->pluck('first_name')->all()
                );

                $rooms = collect($props['rooms']);
                $this->assertSame(['id' => $room->id, 'name' => 'AAA Probebühne'], $rooms->firstWhere('id', $room->id));
                $this->assertNull($rooms->firstWhere('id', $trashedRoom->id));
                $this->assertSame(Room::query()->orderBy('name')->pluck('name')->all(), $rooms->pluck('name')->all());

                $manufacturers = collect($props['manufacturers']);
                $this->assertSame(
                    [
                        ['id' => $manufacturerA->id, 'name' => 'AAA Hersteller'],
                        ['id' => $manufacturerB->id, 'name' => 'AAB Hersteller'],
                    ],
                    $manufacturers
                        ->filter(fn (array $manufacturer): bool => in_array(
                            $manufacturer['id'],
                            [$manufacturerA->id, $manufacturerB->id],
                            true
                        ))
                        ->values()
                        ->all()
                );
                $this->assertNull($manufacturers->firstWhere('id', $trashedManufacturer->id));
                $this->assertNull($manufacturers->firstWhere('id', $otherContact->id));
            });
    }

    private function manufacturerType(): CrmContactType
    {
        return CrmContactType::query()->firstOrCreate(
            ['slug' => CrmSystemContactTypeEnum::MANUFACTURER->value],
            ['name' => 'Hersteller']
        );
    }
}
