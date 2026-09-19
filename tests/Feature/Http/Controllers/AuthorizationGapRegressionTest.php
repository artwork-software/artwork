<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Http\Requests\StoreWorkTimeBookingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class AuthorizationGapRegressionTest extends FeatureTestCase
{
    #[Test]
    #[DataProvider('moneySourceAccess')]
    public function money_source_files_require_access_to_the_actual_source(string $operation, string $access): void
    {
        $user = User::factory()->create();
        $source = MoneySource::factory()->create();
        $file = $source->moneySourceFiles()->create(['name' => 'original.pdf', 'basename' => 'original.pdf']);
        Storage::put('money_source_files/original.pdf', 'confidential');

        $this->actingAs($user);
        if ($access === 'permission') {
            $this->actingAsUserWith('view edit add money_sources', $user);
        } elseif ($access === 'admin') {
            $this->actingAsAdmin($user);
        } elseif (in_array($access, ['write_access', 'competent'], true)) {
            $source->users()->attach($user, [$access => true]);
        } elseif ($access === 'other_source') {
            MoneySource::factory()->create()->users()->attach($user, ['write_access' => true]);
        } elseif ($access === 'creator') {
            $source->forceFill(['creator_id' => $user->id])->save();
        } elseif ($access === 'budget_admin') {
            $this->actingAsUserWith('can manage global project budgets', $user);
        }

        $payload = ['file' => UploadedFile::fake()->create('replacement.pdf', 1), 'comment' => 'Changed'];
        $response = match ($operation) {
            'store' => $this->post(route('money_sources_files.store', $source), $payload),
            'update' => $this->post(route('money_sources_files.update', $file), $payload),
            'download' => $this->get(route('money_sources_download_file', $file)),
            'delete' => $this->delete(route('money_sources_delete_file', [$source, $file])),
        };

        if (in_array($access, ['none', 'other_source'], true)) {
            $response->assertForbidden();
            $this->assertDatabaseCount('money_source_files', 1);
            $this->assertDatabaseHas('money_source_files', [
                'id' => $file->id, 'name' => 'original.pdf', 'basename' => 'original.pdf', 'deleted_at' => null,
            ]);
            $this->assertSame(0, $file->comments()->count());
            $this->assertSame('confidential', Storage::get('money_source_files/original.pdf'));
            $this->assertCount(1, Storage::allFiles('money_source_files'));
            return;
        }

        if ($operation === 'download') {
            $response->assertDownload('original.pdf');
            $this->assertSame('confidential', $response->streamedContent());
        } else {
            $response->assertRedirect();
            if ($operation === 'delete') {
                $this->assertSoftDeleted($file);
            } else {
                $this->assertDatabaseHas('money_source_files', [
                    'money_source_id' => $source->id, 'name' => 'replacement.pdf',
                ]);
            }
        }
    }

    public static function moneySourceAccess(): iterable
    {
        foreach (['store', 'update', 'download', 'delete'] as $operation) {
            $accessLevels = [
                'none', 'other_source', 'permission', 'admin',
                'write_access', 'competent', 'creator', 'budget_admin',
            ];
            foreach ($accessLevels as $access) {
                yield "$operation $access" => [$operation, $access];
            }
        }
    }

    #[Test]
    public function a_file_cannot_be_deleted_through_another_money_source(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $allowedSource = MoneySource::factory()->create(['creator_id' => $user->id]);
        $foreignSource = MoneySource::factory()->create();
        $file = $foreignSource->moneySourceFiles()->create(['name' => 'private.pdf', 'basename' => 'private.pdf']);

        $this->delete(route('money_sources_delete_file', [$allowedSource, $file]))->assertNotFound();

        $this->assertNotSoftDeleted($file);
    }

    #[Test]
    #[DataProvider('contractTypeAccess')]
    public function contract_type_writes_require_project_settings_permission(string $operation, bool $allowed): void
    {
        $type = ContractType::factory()->create(['name' => 'Original', 'color' => '#123456']);
        if (in_array($operation, ['restore', 'force'], true)) {
            $type->delete();
        }
        $before = $type->fresh()->getAttributes();
        if ($allowed) {
            $this->actingAsUserWith('change project settings');
        } else {
            $this->actingAs(User::factory()->create());
        }

        $payload = ['name' => 'Changed', 'color' => '#abcdef'];
        $response = match ($operation) {
            'store' => $this->post(route('contract_types.store'), $payload),
            'update' => $this->patch(route('contract_types.update', $type), $payload),
            'delete' => $this->delete(route('contract_types.delete', $type)),
            'restore' => $this->patch(route('contract_types.restore', $type->id)),
            'force' => $this->delete(route('contract_types.force', $type->id)),
        };

        if (!$allowed) {
            $response->assertForbidden();
            $this->assertSame($before, $type->fresh()->getAttributes());
            $this->assertDatabaseMissing('contract_types', ['name' => 'Changed']);
            return;
        }

        $response->assertRedirect();
        match ($operation) {
            'store', 'update' => $this->assertDatabaseHas('contract_types', $payload),
            'delete' => $this->assertSoftDeleted($type),
            'restore' => $this->assertNotSoftDeleted($type),
            'force' => $this->assertDatabaseMissing('contract_types', ['id' => $type->id]),
        };
    }

    public static function contractTypeAccess(): iterable
    {
        foreach (['store', 'update', 'delete', 'restore', 'force'] as $operation) {
            yield "$operation denied" => [$operation, false];
            yield "$operation allowed" => [$operation, true];
        }
    }

    #[Test]
    #[DataProvider('workTimeAccess')]
    public function manual_work_time_bookings_require_worker_management(string $access, bool $self, string $sign): void
    {
        $actor = User::factory()->create(['work_time_balance' => 100]);
        $target = $self ? $actor : User::factory()->create(['work_time_balance' => 100]);
        $this->actingAs($actor);
        if ($access === 'permission') {
            $this->actingAsUserWith('can manage workers', $actor);
        } elseif ($access === 'admin') {
            $this->actingAsAdmin($actor);
        }

        $response = $this->post(route('users.worktimes.store', $target), [
            'user_id' => $target->id,
            'date' => '2026-09-17',
            'hours' => '01:30',
            'nightly_working_hours' => '00:30',
            'plus_minus' => $sign,
            'comment' => 'Manual adjustment',
        ]);

        if ($access === 'none') {
            $response->assertForbidden();
            $this->assertSame(0, $target->workTimeBookings()->count());
            $this->assertSame(100, $target->fresh()->work_time_balance);
        } else {
            $response->assertSuccessful();
            $delta = $sign === '+' ? 90 : -90;
            $this->assertSame(100 + $delta, $target->fresh()->work_time_balance);
            $this->assertDatabaseHas('work_time_bookings', [
                'user_id' => $target->id, 'booker_id' => $actor->id,
                'worked_hours' => $delta, 'nightly_working_hours' => 30,
            ]);
        }
    }

    public static function workTimeAccess(): iterable
    {
        foreach (['none', 'permission', 'admin'] as $access) {
            foreach ([false, true] as $self) {
                foreach (['+', '-'] as $sign) {
                    yield "$access self=$self $sign" => [$access, $self, $sign];
                }
            }
        }
    }

    #[Test]
    public function work_time_form_request_checks_permission_independently(): void
    {
        $request = new StoreWorkTimeBookingRequest();
        $request->setUserResolver(fn () => null);
        $this->assertFalse($request->authorize());

        $user = User::factory()->create();
        $request->setUserResolver(fn () => $user);
        $this->assertFalse($request->authorize());

        $this->actingAsUserWith('can manage workers', $user);
        $this->assertTrue($request->authorize());
    }
}
