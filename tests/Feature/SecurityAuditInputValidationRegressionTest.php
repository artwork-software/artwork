<?php

namespace Tests\Feature;

use Artwork\Core\Validation\Rules\PublicUrlRule;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\ExternalUserManagement\Api\LdapApi;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Webhook\Models\WebhookEndpoint;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Eingabeverarbeitung: Mass Assignment, Validierung, SSRF.
 */
final class SecurityAuditInputValidationRegressionTest extends FeatureTestCase
{
    // ---------- Checklist-Mass-Assignment ----------

    #[Test]
    public function checklist_update_cannot_move_checklist_into_foreign_project_or_change_owner(): void
    {
        $owner = $this->actingAsUserWith(PermissionEnum::CHECKLIST_EDIT_PERMISSION->value);
        $ownProject = Project::factory()->create();
        $ownProject->users()->attach($owner->id);
        $foreignProject = Project::factory()->create();
        $stranger = User::factory()->create();

        $checklist = Checklist::factory()->create([
            'project_id' => $ownProject->id,
            'user_id' => $owner->id,
            'tab_id' => 7,
        ]);

        $this->patch(route('checklists.update', $checklist), [
            'name' => 'umbenannt',
            'project_id' => $foreignProject->id,
            'user_id' => $stranger->id,
        ])->assertForbidden();

        $checklist->refresh();
        $this->assertSame($ownProject->id, $checklist->project_id);
        $this->assertSame($owner->id, $checklist->user_id);
        $this->assertSame(7, $checklist->tab_id);
    }

    #[Test]
    public function checklist_update_keeps_owner_project_and_tab_when_body_sends_nulls(): void
    {
        $owner = $this->actingAsUserWith(PermissionEnum::CHECKLIST_EDIT_PERMISSION->value);
        $project = Project::factory()->create();
        $project->users()->attach($owner->id);
        $checklist = Checklist::factory()->create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'tab_id' => 3,
        ]);

        $this->patch(route('checklists.update', $checklist), [
            'name' => 'neuer Name',
            'private' => false,
            'project_id' => null,
            'tab_id' => null,
            'user_id' => null,
        ])->assertRedirect();

        $checklist->refresh();
        $this->assertSame('neuer Name', $checklist->name);
        $this->assertSame($project->id, $checklist->project_id);
        $this->assertSame($owner->id, $checklist->user_id);
        $this->assertSame(3, $checklist->tab_id);
    }

    #[Test]
    public function checklist_update_allows_move_into_project_the_user_may_edit(): void
    {
        $owner = $this->actingAsUserWith(PermissionEnum::CHECKLIST_EDIT_PERMISSION->value);
        $target = Project::factory()->create();
        $target->users()->attach($owner->id);
        $tab = ProjectTab::factory()->create();
        $checklist = Checklist::factory()->create(['project_id' => null, 'user_id' => $owner->id]);

        $this->patch(route('checklists.update', $checklist), [
            'name' => $checklist->name,
            'project_id' => $target->id,
            'tab_id' => $tab->id,
        ])->assertRedirect();

        $checklist->refresh();
        $this->assertSame($target->id, $checklist->project_id);
        $this->assertSame($tab->id, $checklist->tab_id);
    }

    #[Test]
    public function checklist_update_rejects_unknown_tab_and_overlong_name(): void
    {
        $owner = $this->actingAsUserWith(PermissionEnum::CHECKLIST_EDIT_PERMISSION->value);
        $checklist = Checklist::factory()->create(['project_id' => null, 'user_id' => $owner->id]);

        $this->patch(route('checklists.update', $checklist), ['tab_id' => 999999])
            ->assertSessionHasErrors('tab_id');
        $this->patch(route('checklists.update', $checklist), ['name' => str_repeat('a', 256)])
            ->assertSessionHasErrors('name');
    }

    // ---------- MoneySource ----------

    #[Test]
    public function money_source_store_validates_amount_and_group_references(): void
    {
        $this->actingAsUserWith(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);

        $this->post(route('money_sources.store'), [
            'name' => 'Quelle',
            'amount' => 'abc',
            'group_id' => 999999,
            'sub_money_source_ids' => [999998],
            'users' => [],
        ])->assertSessionHasErrors(['amount', 'group_id', 'sub_money_source_ids.0']);

        $this->post(route('money_sources.store'), ['amount' => '10', 'users' => []])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function money_source_store_normalises_comma_amounts(): void
    {
        $this->actingAsUserWith(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);

        $this->post(route('money_sources.store'), [
            'name' => 'Komma',
            'amount' => '1234,50',
            'users' => [],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('money_sources', ['name' => 'Komma', 'amount' => 1234.5]);
    }

    #[Test]
    public function money_source_cannot_attach_foreign_sources_as_sub_sources(): void
    {
        // MONEY_SOURCE_EDIT_VIEW_ADD berechtigt in der Policy zum Bearbeiten aller Quellen; daher nur creator-Recht.
        $user = $this->actingAsUserWith(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);
        $own = MoneySource::factory()->create(['creator_id' => $user->id, 'is_group' => true]);
        $foreign = MoneySource::factory()->create();
        $user->revokePermissionTo(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);
        $user->unsetRelation('permissions');

        $this->patch(route('money_sources.update', $own), [
            'name' => $own->name,
            'amount' => '1',
            'is_group' => true,
            'sub_money_source_ids' => [$foreign->id],
            'users' => [],
        ])->assertForbidden();

        $this->assertNull($foreign->fresh()->group_id);
    }

    #[Test]
    public function money_source_cannot_be_moved_into_foreign_group(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);
        $own = MoneySource::factory()->create(['creator_id' => $user->id]);
        $foreignGroup = MoneySource::factory()->create(['is_group' => true]);
        $user->revokePermissionTo(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value);
        $user->unsetRelation('permissions');

        $this->patch(route('money_sources.update', $own), [
            'name' => $own->name,
            'amount' => '1',
            'is_group' => false,
            'group_id' => $foreignGroup->id,
            'users' => [],
        ])->assertForbidden();

        $this->assertNull($own->fresh()->group_id);
    }

    // ---------- Checklist-/Task-Templates ----------

    #[Test]
    public function checklist_template_store_forces_creator_to_current_user(): void
    {
        $admin = $this->actingAsUserWith(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);
        $stranger = User::factory()->create();

        $this->post(route('checklist_templates.store'), [
            'name' => 'Vorlage',
            'user_id' => $stranger->id,
            'users' => [],
            'task_templates' => [
                ['name' => 'Aufgabe', 'description' => 'x', 'deadline_days_after_creation' => 2, 'checklist_template_id' => 999],
            ],
        ])->assertRedirect();

        $template = ChecklistTemplate::query()->where('name', 'Vorlage')->firstOrFail();
        $this->assertSame($admin->id, $template->user_id);
        $this->assertSame(1, $template->task_templates()->count());
        $this->assertSame($template->id, $template->task_templates()->first()->checklist_template_id);
    }

    #[Test]
    public function checklist_template_store_validates_task_templates(): void
    {
        $this->actingAsUserWith(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $this->post(route('checklist_templates.store'), [
            'name' => 'Vorlage',
            'task_templates' => [['description' => 'ohne Name', 'deadline_days_after_creation' => -1]],
        ])->assertSessionHasErrors(['task_templates.0.name', 'task_templates.0.deadline_days_after_creation']);

        $this->post(route('checklist_templates.store'), ['task_templates' => []])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function task_template_requires_existing_checklist_template(): void
    {
        $this->actingAsUserWith(PermissionEnum::CHECKLIST_SETTINGS_ADMIN->value);

        $this->post(route('task_templates.store'), [
            'name' => 'Aufgabe',
            'checklist_template_id' => 999999,
        ])->assertSessionHasErrors('checklist_template_id');

        $template = ChecklistTemplate::factory()->create();
        $task = TaskTemplate::factory()->create(['checklist_template_id' => $template->id]);

        $this->patch(route('task_templates.update', $task), ['checklist_template_id' => 999999])
            ->assertSessionHasErrors('checklist_template_id');
        $this->assertSame($template->id, $task->fresh()->checklist_template_id);
    }

    // ---------- EventType ----------

    #[Test]
    public function event_type_rejects_unknown_verification_mode_and_verifier(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_SETTINGS_UPDATE->value);
        $eventType = EventType::factory()->create(['verification_mode' => 'none']);

        $this->patch(route('event_types.update', $eventType), [
            'name' => $eventType->name,
            'verification_mode' => 'off',
        ])->assertSessionHasErrors('verification_mode');

        $this->patch(route('event_types.update', $eventType), [
            'name' => $eventType->name,
            'verification_mode' => 'specific',
            'specific_verifier_id' => 999999,
        ])->assertSessionHasErrors('specific_verifier_id');

        $this->assertSame('none', $eventType->fresh()->verification_mode);
    }

    #[Test]
    public function event_type_accepts_valid_verification_settings(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_SETTINGS_UPDATE->value);
        $verifier = User::factory()->create();
        $eventType = EventType::factory()->create(['verification_mode' => 'none']);

        $this->patch(route('event_types.update', $eventType), [
            'name' => 'Probe',
            'verification_mode' => 'specific',
            'specific_verifier_id' => $verifier->id,
            'users' => [['id' => $verifier->id]],
        ])->assertRedirect();

        $eventType->refresh();
        $this->assertSame('specific', $eventType->verification_mode);
        $this->assertSame($verifier->id, $eventType->specific_verifier_id);
    }

    // ---------- PrintLayout ----------

    #[Test]
    public function print_layout_update_ignores_owner_permission_default_and_order(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);
        $stranger = User::factory()->create();
        $layout = ProjectPrintLayout::query()->create([
            'name' => 'Layout',
            'description' => null,
            'is_default' => false,
            'columns_header' => 1,
            'columns_footer' => 1,
            'columns_body' => 1,
            'order' => 1,
            'is_active' => true,
            'user_id' => $user->id,
            'permission' => 'private',
            'notes' => '',
        ]);

        $this->patch(route('project-print-layout.update', $layout), [
            'name' => 'Neu',
            'columns_header' => 2,
            'columns_footer' => 1,
            'columns_body' => 1,
            'is_active' => true,
            'user_id' => $stranger->id,
            'permission' => 'public',
            'is_default' => true,
            'order' => 99,
        ]);

        $layout->refresh();
        $this->assertSame('Neu', $layout->name);
        $this->assertSame(2, $layout->columns_header);
        $this->assertSame($user->id, $layout->user_id);
        $this->assertSame('private', $layout->permission);
        $this->assertFalse($layout->is_default);
        $this->assertSame(1, $layout->order);
    }

    // ---------- SSRF Webhook / OIDC ----------

    #[Test]
    public function webhook_endpoint_rejects_private_and_unresolvable_targets(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);
        $payload = fn (string $url): array => [
            'name' => 'Hook',
            'url' => $url,
            'subscribed_events' => array_slice(array_keys(config('webhooks.events', [])), 0, 1),
        ];

        $this->post(route('webhooks.store'), $payload('https://127.0.0.1/hook'))->assertSessionHasErrors('url');
        $this->post(route('webhooks.store'), $payload('https://169.254.169.254/latest'))->assertSessionHasErrors('url');
        $this->post(route('webhooks.store'), $payload('https://[::1]/hook'))->assertSessionHasErrors('url');

        PublicUrlRule::resolveUsing(static fn (): array => ['10.1.2.3']);
        $this->post(route('webhooks.store'), $payload('https://intranet.example.test/hook'))
            ->assertSessionHasErrors('url');

        PublicUrlRule::resolveUsing(static fn (): array => []);
        $this->post(route('webhooks.store'), $payload('https://nowhere.example.test/hook'))
            ->assertSessionHasErrors('url');

        $this->assertSame(0, WebhookEndpoint::query()->count());

        PublicUrlRule::resolveUsing(static fn (): array => ['203.0.113.10']);
        $this->post(route('webhooks.store'), $payload('https://shop.example.test/hook'))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, WebhookEndpoint::query()->count());
    }

    // ---------- ProjectRole / Component ----------

    #[Test]
    public function project_role_requires_name(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $this->post(route('project-roles.store'), [])->assertSessionHasErrors('name');
        $this->post(route('project-roles.store'), ['name' => str_repeat('x', 256)])->assertSessionHasErrors('name');
        $this->post(route('project-roles.store'), ['name' => 'Regie'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('project_roles', ['name' => 'Regie']);

        $role = ProjectRole::query()->where('name', 'Regie')->firstOrFail();
        $this->patch(route('project-roles.update', $role), ['name' => ''])->assertSessionHasErrors('name');
    }

    #[Test]
    public function component_store_validates_type_permission_type_and_pivots(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $this->post(route('component.store'), [
            'name' => 'Feld',
            'type' => 'NotAComponent',
            'permission_type' => 'everyone',
            'users' => [['user_id' => 999999, 'can_write' => 'maybe']],
            'departments' => [['department_id' => 999999]],
        ])->assertSessionHasErrors([
            'type',
            'permission_type',
            'users.0.user_id',
            'users.0.can_write',
            'departments.0.department_id',
        ]);

        $this->assertSame(0, Component::query()->where('name', 'Feld')->count());

        $this->post(route('component.store'), [
            'name' => 'Feld',
            'type' => 'TextField',
            'data' => ['placeholder' => 'x'],
            'permission_type' => 'allSeeAndEdit',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('components', ['name' => 'Feld', 'type' => 'TextField']);
    }

    // ---------- Chat / Kommentar / Tool-Settings ----------

    #[Test]
    public function chat_message_is_limited(): void
    {
        $user = $this->actingAsAdmin();
        $chat = Chat::query()->create(['name' => 'Test', 'is_group' => true, 'created_by' => $user->id]);
        $chat->users()->attach($user->id);

        $this->postJson(route('chat-system.send-message', $chat), ['message' => str_repeat('a', 10001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
        $this->postJson(route('chat-system.send-message', $chat), ['message' => ''])
            ->assertUnprocessable();
    }

    #[Test]
    public function comment_update_uses_same_limit_as_store(): void
    {
        $user = $this->actingAsAdmin();
        $project = Project::factory()->create();
        $comment = Comment::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'text' => 'alt']);

        $this->patch('/comments/' . $comment->id, ['text' => str_repeat('a', 5001)])
            ->assertSessionHasErrors('text');
        $this->patch('/comments/' . $comment->id, ['text' => ''])
            ->assertSessionHasErrors('text');
        $this->assertSame('alt', $comment->fresh()->text);
    }

    #[Test]
    public function tool_settings_reject_javascript_links_and_invalid_emails(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->patch(route('tool.communication-and-legal.update'), [
            'impressumLink' => 'javascript:alert(1)',
            'privacyLink' => 'ftp://example.test/privacy',
            'invitationEmail' => 'nicht-mail',
            'businessEmail' => 'auch-nicht',
            'letterheadEmail' => 'x',
        ])->assertSessionHasErrors([
            'impressumLink',
            'privacyLink',
            'invitationEmail',
            'businessEmail',
            'letterheadEmail',
        ]);

        $this->patch(route('tool.communication-and-legal.update'), [
            'impressumLink' => 'https://example.test/impressum',
            'privacyLink' => '',
            'invitationEmail' => 'mail@example.test',
        ])->assertSessionHasNoErrors();
    }

    // ---------- LDAP identifier whitelist ----------

    #[Test]
    public function ldap_identifier_attribute_is_whitelisted(): void
    {
        $this->assertSame('objectGUID', LdapApi::identifierAttribute([]));
        $this->assertSame('sAMAccountName', LdapApi::identifierAttribute(['identifier_attribute' => 'samaccountname']));
        $this->assertSame('mail', LdapApi::identifierAttribute(['identifier_attribute' => 'mail']));

        $this->expectException(InvalidArgumentException::class);
        LdapApi::identifierAttribute(['identifier_attribute' => 'mail=*)(objectClass=*']);
    }
}
