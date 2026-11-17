<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceCategory;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Finanzierungsquellen: Personen, Projekte und Kategorien zuordnen, Quelle duplizieren
 * und Aufgaben über die ältere Route erledigen.
 */
final class MoneySourceRelationsAndDuplicateTest extends FeatureTestCase
{
    // ---------- Personen ----------

    #[Test]
    public function updating_users_syncs_rights_and_task_assignees_follow_the_competent_users(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $removed = User::factory()->create();
        $source->users()->attach($removed->id, ['competent' => true, 'write_access' => true]);
        $task = MoneySourceTask::factory()->create(['money_source_id' => $source->id]);
        $task->money_source_task_users()->attach($removed->id);
        $competent = User::factory()->create();
        $writer = User::factory()->create();

        $this->patch(route('money_sources.update_users', $source), [
            'users' => [
                $competent->id => ['user_id' => $competent->id, 'competent' => true, 'write_access' => false],
                $writer->id => ['user_id' => $writer->id, 'competent' => false, 'write_access' => true],
            ],
        ])->assertSuccessful();

        $this->assertDatabaseMissing('money_source_users', ['money_source_id' => $source->id, 'user_id' => $removed->id]);
        $this->assertDatabaseHas('money_source_users', [
            'money_source_id' => $source->id,
            'user_id' => $competent->id,
            'competent' => true,
            'write_access' => false,
        ]);
        $this->assertDatabaseHas('money_source_users', [
            'money_source_id' => $source->id,
            'user_id' => $writer->id,
            'competent' => false,
            'write_access' => true,
        ]);
        $this->assertSame([$competent->id], $task->money_source_task_users()->pluck('users.id')->all());
    }

    #[Test]
    public function updating_users_cannot_write_pivot_rows_for_another_money_source(): void
    {
        $creator = $this->actingAsUserWith([]);
        $ownSource = MoneySource::factory()->create(['creator_id' => $creator->id]);
        $foreignSource = MoneySource::factory()->create();

        $this->patch(route('money_sources.update_users', $ownSource), [
            'users' => [
                $creator->id => [
                    'user_id' => $creator->id,
                    'competent' => true,
                    'write_access' => true,
                    'money_source_id' => $foreignSource->id,
                ],
            ],
        ])->assertSuccessful();

        $this->assertDatabaseMissing('money_source_users', [
            'money_source_id' => $foreignSource->id,
            'user_id' => $creator->id,
        ]);
        $this->assertDatabaseHas('money_source_users', [
            'money_source_id' => $ownSource->id,
            'user_id' => $creator->id,
        ]);
        $this->assertFalse($creator->fresh()->can('update', $foreignSource->fresh()));
    }

    #[Test]
    public function updating_users_with_unknown_user_is_a_validation_error(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $this->patchJson(route('money_sources.update_users', $source), [
            'users' => [999999999 => ['user_id' => 999999999, 'competent' => true, 'write_access' => true]],
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('money_source_users', ['money_source_id' => $source->id]);
    }

    #[Test]
    public function updating_users_with_an_empty_list_removes_everybody(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $member = User::factory()->create();
        $source->users()->attach($member->id, ['competent' => false, 'write_access' => true]);

        $this->patch(route('money_sources.update_users', $source), ['users' => []])->assertSuccessful();

        $this->assertDatabaseMissing('money_source_users', ['money_source_id' => $source->id]);
    }

    // ---------- Projekte ----------

    #[Test]
    public function updating_projects_syncs_the_linked_projects(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $removed = Project::factory()->create();
        $kept = Project::factory()->create();
        $added = Project::factory()->create();
        $source->projects()->attach([$removed->id, $kept->id]);

        $this->patch(route('money_sources.update_projects', $source), [
            'linkedProjectIds' => [$kept->id, $added->id],
        ])->assertSuccessful();

        $this->assertEqualsCanonicalizing([$kept->id, $added->id], $source->projects()->pluck('projects.id')->all());
    }

    #[Test]
    public function updating_projects_with_an_empty_list_unlinks_all(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $source->projects()->attach(Project::factory()->create()->id);

        $this->patch(route('money_sources.update_projects', $source), ['linkedProjectIds' => []])
            ->assertSuccessful();

        $this->assertSame(0, $source->projects()->count());
    }

    #[Test]
    public function updating_projects_without_the_list_keeps_the_existing_links(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $project = Project::factory()->create();
        $source->projects()->attach($project->id);

        $this->patchJson(route('money_sources.update_projects', $source), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('linkedProjectIds');

        $this->assertSame([$project->id], $source->projects()->pluck('projects.id')->all());
    }

    #[Test]
    public function updating_projects_with_an_unknown_project_is_a_validation_error(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $this->patchJson(route('money_sources.update_projects', $source), ['linkedProjectIds' => [999999999]])
            ->assertUnprocessable();

        $this->assertSame(0, $source->projects()->count());
    }

    // ---------- Kategorien ----------

    #[Test]
    public function syncing_categories_replaces_the_assigned_categories(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $old = MoneySourceCategory::factory()->create();
        $new = MoneySourceCategory::factory()->create();
        $source->categories()->attach($old->id);

        $this->post(route('money_sources.categories.sync', $source), ['categoryIds' => [$new->id]])
            ->assertSuccessful();

        $this->assertSame([$new->id], $source->categories()->pluck('money_source_categories.id')->all());
    }

    #[Test]
    public function syncing_an_unknown_category_is_a_validation_error_instead_of_500(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $category = MoneySourceCategory::factory()->create();
        $source->categories()->attach($category->id);

        $this->postJson(route('money_sources.categories.sync', $source), ['categoryIds' => [999999999]])
            ->assertUnprocessable();

        $this->assertSame([$category->id], $source->categories()->pluck('money_source_categories.id')->all());
    }

    #[Test]
    public function syncing_categories_without_the_list_keeps_the_existing_categories(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $category = MoneySourceCategory::factory()->create();
        $source->categories()->attach($category->id);

        $this->postJson(route('money_sources.categories.sync', $source), [])->assertUnprocessable();

        $this->assertSame(1, $source->categories()->count());
    }

    // ---------- Duplizieren ----------

    #[Test]
    public function duplicating_copies_the_fields_and_makes_the_current_user_creator(): void
    {
        $admin = $this->actingAsAdmin();
        $source = MoneySource::factory()->create([
            'name' => 'Landesförderung',
            'amount' => 1234.5,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'funding_start_date' => '2026-02-01',
            'funding_end_date' => '2026-11-30',
            'source_name' => 'Land',
            'description' => 'Beschreibung',
        ]);

        $this->post(route('money_sources.duplicate', $source))->assertRedirect(route('money_sources.index'));

        $copy = MoneySource::query()->whereKeyNot($source->id)->where('source_name', 'Land')->sole();
        $this->assertStringEndsWith('Landesförderung', $copy->name);
        $this->assertNotSame($source->name, $copy->name);
        $this->assertSame($admin->id, (int) $copy->creator_id);
        $this->assertEquals(1234.5, $copy->amount);
        $this->assertSame('2026-01-01', (string) $copy->start_date);
        $this->assertSame('2026-12-31', (string) $copy->end_date);
        $this->assertSame('2026-02-01', (string) $copy->funding_start_date);
        $this->assertSame('2026-11-30', (string) $copy->funding_end_date);
        $this->assertSame('Beschreibung', $copy->description);
        $this->assertSame('Landesförderung', $source->fresh()->name);
    }

    // ---------- Aufgaben (ältere Route money_source.tasks.update) ----------

    #[Test]
    public function an_assignee_can_mark_a_task_done_via_the_legacy_route(): void
    {
        $assignee = $this->actingAsUserWith([]);
        $task = MoneySourceTask::factory()->create(['done' => false]);
        $task->money_source_task_users()->attach($assignee->id);

        $this->patch(route('money_source.tasks.update', $task))->assertRedirect();

        $this->assertTrue((bool) $task->fresh()->done);
    }

    #[Test]
    public function marking_an_already_done_task_done_again_keeps_it_done(): void
    {
        $this->actingAsAdmin();
        $task = MoneySourceTask::factory()->create(['done' => true]);

        $this->patch(route('money_source.tasks.update', $task))->assertRedirect();

        $this->assertTrue((bool) $task->fresh()->done);
    }

    #[Test]
    public function marking_an_unknown_task_done_returns_404(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('money_source.tasks.update', 999999999))->assertNotFound();
    }
}
