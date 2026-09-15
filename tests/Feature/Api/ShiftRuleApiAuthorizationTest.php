<?php

namespace Tests\Feature\Api;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-schritt1-rueckfluss, Rueckfluss 1 aus Konzept 0.1 §4.
 *
 * PATCH /api/shift-rules/violations/{id}/status war schreibend und ohne jede Rechtepruefung —
 * weder Route noch FormRequest noch Service haben geprueft. Jeder eingeloggte User konnte fremde
 * Regelverstoesse auf "resolved" oder "ignored" setzen.
 */
final class ShiftRuleApiAuthorizationTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function shiftRuleRoutes(): array
    {
        return [
            'Verstoss-Status setzen' => ['patch', 'api.shift-rules.update-status', ['status' => 'resolved']],
            'Regeln pruefen' => ['post', 'api.shift-rules.validate', [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-02',
            ]],
            'offene Verstoesse' => ['get', 'api.shift-rules.pending', []],
        ];
    }

    private function url(string $routeName): string
    {
        return $routeName === 'api.shift-rules.update-status'
            ? route($routeName, ['violationId' => 1])
            : route($routeName);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('shiftRuleRoutes')]
    public function a_user_without_the_shift_planning_permission_is_rejected(
        string $method,
        string $routeName,
        array $payload
    ): void {
        $this->actingAs(User::factory()->create());

        $this->{$method}($this->url($routeName), $payload)->assertForbidden();
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('shiftRuleRoutes')]
    public function a_user_with_the_shift_planning_permission_gets_through(
        string $method,
        string $routeName,
        array $payload
    ): void {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        $response = $this->{$method}($this->url($routeName), $payload);

        $this->assertNotSame(403, $response->getStatusCode(), 'Die Route darf mit Recht nicht sperren');
    }

    #[Test]
    public function a_guest_is_not_authenticated(): void
    {
        $this->patchJson($this->url('api.shift-rules.update-status'), ['status' => 'resolved'])
            ->assertUnauthorized();
    }

    /** Die Pruefung haengt zusaetzlich am FormRequest, damit sie ein Verschieben der Route ueberlebt. */
    #[Test]
    public function the_form_request_denies_without_the_permission(): void
    {
        $request = new \Artwork\Modules\Shift\Http\Requests\UpdateViolationStatusRequest();
        $request->setUserResolver(fn (): User => User::factory()->create());

        $this->assertFalse($request->authorize());
    }
}
