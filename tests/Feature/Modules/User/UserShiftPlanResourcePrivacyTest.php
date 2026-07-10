<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserShiftPlanResourcePrivacyTest extends FeatureTestCase
{
    private function makeTarget(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'phone_number' => '+49 40 123456',
            'email_private' => true,
            'phone_private' => true,
        ], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceArrayFor(?User $viewer, User $target): array
    {
        $target->setRelation('shifts', collect());

        $request = Request::create('/shift-plan');
        $request->setUserResolver(static fn (): ?User => $viewer);

        return (new UserShiftPlanResource($target))
            ->setStartDate(Carbon::parse('2026-08-10'))
            ->setEndDate(Carbon::parse('2026-08-16'))
            ->toArray($request);
    }

    #[Test]
    public function it_hides_private_contact_data_from_viewers_without_permission(): void
    {
        $viewer = User::factory()->create();
        $target = $this->makeTarget();

        $data = $this->resourceArrayFor($viewer, $target);

        $this->assertNull($data['email']);
        $this->assertNull($data['phone_number']);
        $this->assertTrue($data['email_private']);
        $this->assertTrue($data['phone_private']);
    }

    #[Test]
    public function it_shows_private_contact_data_to_viewers_with_permission(): void
    {
        $viewer = $this->actingAsUserWith([PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO]);
        $target = $this->makeTarget();

        $data = $this->resourceArrayFor($viewer, $target);

        $this->assertSame($target->email, $data['email']);
        $this->assertSame('+49 40 123456', $data['phone_number']);
    }

    #[Test]
    public function it_shows_non_private_contact_data_to_any_viewer(): void
    {
        $viewer = User::factory()->create();
        $target = $this->makeTarget([
            'email_private' => false,
            'phone_private' => false,
        ]);

        $data = $this->resourceArrayFor($viewer, $target);

        $this->assertSame($target->email, $data['email']);
        $this->assertSame('+49 40 123456', $data['phone_number']);
    }

    #[Test]
    public function it_hides_private_contact_data_when_no_request_user_is_present(): void
    {
        $target = $this->makeTarget();

        $data = $this->resourceArrayFor(null, $target);

        $this->assertNull($data['email']);
        $this->assertNull($data['phone_number']);
    }
}
