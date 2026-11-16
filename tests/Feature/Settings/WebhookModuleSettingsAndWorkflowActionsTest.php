<?php

namespace Tests\Feature\Settings;

use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Webhook\Enums\WebhookDeliveryStatus;
use Artwork\Modules\Webhook\Models\WebhookDelivery;
use Artwork\Modules\Webhook\Models\WebhookEndpoint;
use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktionstests für Webhook-Endpunkte (Bearbeiten/Löschen), Moduleinstellungen und Workflows.
 */
final class WebhookModuleSettingsAndWorkflowActionsTest extends FeatureTestCase
{
    private function endpoint(): WebhookEndpoint
    {
        return WebhookEndpoint::query()->create([
            'name' => 'Ticketshop',
            'url' => 'https://shop.example.test/hooks',
            'secret' => 'geheim-123',
            'subscribed_events' => ['webhook.test'],
            'is_active' => true,
        ]);
    }

    #[Test]
    public function a_webhook_endpoint_can_be_updated_without_touching_its_secret(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);
        $endpoint = $this->endpoint();

        $this->from('/webhooks')->patch(route('webhooks.update', $endpoint), [
            'name' => 'Kasse',
            'url' => 'https://kasse.example.test/in',
            'subscribed_events' => ['webhook.test'],
            'is_active' => false,
        ])->assertRedirect('/webhooks')->assertSessionHas('success');

        $endpoint->refresh();
        $this->assertSame('Kasse', $endpoint->name);
        $this->assertSame('https://kasse.example.test/in', $endpoint->url);
        $this->assertFalse($endpoint->is_active);
        $this->assertSame('geheim-123', $endpoint->secret);
    }

    #[Test]
    public function updating_a_webhook_endpoint_keeps_it_active_when_the_flag_is_omitted(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);
        $endpoint = $this->endpoint();

        $this->patch(route('webhooks.update', $endpoint), [
            'name' => 'Kasse',
            'url' => 'https://kasse.example.test/in',
            'subscribed_events' => ['webhook.test'],
        ])->assertRedirect();

        $this->assertTrue($endpoint->fresh()->is_active);
    }

    #[Test]
    public function updating_a_webhook_endpoint_validates_url_and_events(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);
        $endpoint = $this->endpoint();

        $this->patchJson(route('webhooks.update', $endpoint), [
            'name' => 'Kasse',
            'url' => 'http://kasse.example.test/in',
            'subscribed_events' => ['gibt.es.nicht'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['url', 'subscribed_events.0']);

        $this->patchJson(route('webhooks.update', $endpoint), [
            'name' => 'Kasse',
            'url' => 'https://kasse.example.test/in',
            'subscribed_events' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('subscribed_events');

        $this->assertSame('Ticketshop', $endpoint->fresh()->name);
    }

    #[Test]
    public function destroying_a_webhook_endpoint_removes_its_deliveries(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);
        $endpoint = $this->endpoint();
        $delivery = WebhookDelivery::query()->create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_name' => 'webhook.test',
            'payload' => ['a' => 1],
            'status' => WebhookDeliveryStatus::EXHAUSTED,
            'attempt' => 6,
        ]);

        $this->from('/webhooks')
            ->delete(route('webhooks.destroy', $endpoint))
            ->assertRedirect('/webhooks')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('webhook_endpoints', ['id' => $endpoint->id]);
        $this->assertDatabaseMissing('webhook_deliveries', ['id' => $delivery->id]);
    }

    #[Test]
    public function a_module_can_be_switched_off_and_on(): void
    {
        $this->actingAsAdmin();

        $this->from('/tool')->patch(route('tool.module-settings.update'), ['menu' => 'inventory', 'enabled' => false])
            ->assertRedirect('/tool')
            ->assertSessionHas('success');
        $this->assertFalse(app(ModuleSettings::class)->refresh()->inventory);

        $this->patch(route('tool.module-settings.update'), ['menu' => 'inventory', 'enabled' => true])->assertRedirect();
        $this->assertTrue(app(ModuleSettings::class)->refresh()->inventory);
    }

    #[Test]
    public function only_module_switches_can_be_changed_through_the_module_settings(): void
    {
        $this->actingAsAdmin();
        $before = DB::table('settings')->where('group', 'general')->where('name', 'inventory_number_prefix')->value('payload');

        $this->patchJson(route('tool.module-settings.update'), ['menu' => 'inventory_number_prefix', 'enabled' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('menu');
        $this->patchJson(route('tool.module-settings.update'), ['menu' => 'gibt_es_nicht', 'enabled' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('menu');
        $this->patchJson(route('tool.module-settings.update'), ['menu' => 'inventory', 'enabled' => 'jein'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('enabled');

        $this->assertSame(
            $before,
            DB::table('settings')->where('group', 'general')->where('name', 'inventory_number_prefix')->value('payload')
        );
        $this->assertFalse(
            DB::table('settings')->where('group', 'module_settings')->where('name', 'inventory_number_prefix')->exists()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function workflowConfig(): array
    {
        return [
            'initial_place' => 'offen',
            'places' => [
                ['name' => 'offen', 'type' => 'start'],
                ['name' => 'in_arbeit', 'type' => 'intermediate'],
                ['name' => 'erledigt', 'type' => 'end'],
            ],
            'transitions' => [
                ['name' => 'beginnen', 'from' => ['offen'], 'to' => 'in_arbeit'],
                ['name' => 'abschliessen', 'from' => ['in_arbeit'], 'to' => 'erledigt'],
            ],
        ];
    }

    private function workflowInstance(array $config, string $currentPlace): WorkflowInstance
    {
        $definition = WorkflowDefinition::query()->create(['name' => 'Test', 'type' => 'test', 'is_active' => true]);
        $definitionConfig = $definition->saveConfig($config);

        return WorkflowInstance::query()->create([
            'workflow_definition_config_id' => $definitionConfig->id,
            'subject_type' => Project::class,
            'subject_id' => Project::factory()->create()->id,
            'current_place' => $currentPlace,
        ]);
    }

    #[Test]
    public function a_workflow_definition_is_stored_with_its_config(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('workflow.definitions.store'), [
            'name' => 'Freigabe',
            'type' => 'project',
            'config' => $this->workflowConfig(),
        ]);

        $definition = WorkflowDefinition::query()->where('name', 'Freigabe')->firstOrFail();
        $response->assertRedirect(route('workflow.definitions.show', $definition));
        $this->assertSame('offen', $definition->currentConfig->getInitialPlace());
    }

    #[Test]
    public function an_invalid_workflow_config_is_rejected_without_leaving_a_definition_behind(): void
    {
        $this->actingAsAdmin();
        $config = $this->workflowConfig();
        $config['initial_place'] = 'unbekannt';

        $this->from('/workflow')->post(route('workflow.definitions.store'), [
            'name' => 'Kaputt',
            'type' => 'project',
            'config' => $config,
        ])->assertRedirect('/workflow')->assertSessionHas('error');

        $this->assertFalse(WorkflowDefinition::query()->where('name', 'Kaputt')->exists());
    }

    #[Test]
    public function transitions_must_lead_to_a_known_place(): void
    {
        $this->actingAsAdmin();
        $config = $this->workflowConfig();
        $config['transitions'][] = ['name' => 'ohne_ziel', 'from' => ['offen']];
        $config['transitions'][] = ['name' => 'ins_leere', 'from' => ['offen'], 'to' => 'nirgendwo'];

        $this->from('/workflow')->post(route('workflow.definitions.store'), [
            'name' => 'Ohne Ziel',
            'type' => 'project',
            'config' => $config,
        ])->assertRedirect('/workflow')->assertSessionHas('error');

        $this->assertFalse(WorkflowDefinition::query()->where('name', 'Ohne Ziel')->exists());
    }

    #[Test]
    public function executing_a_transition_moves_the_instance_and_logs_from_and_to(): void
    {
        $this->actingAsAdmin();
        $instance = $this->workflowInstance($this->workflowConfig(), 'offen');

        $this->from('/instance')
            ->post(route('workflow.instances.execute-transition', $instance), ['transition' => 'beginnen'])
            ->assertRedirect('/instance')
            ->assertSessionHas('success');

        $instance->refresh();
        $this->assertSame('in_arbeit', $instance->current_place);
        $this->assertNull($instance->completed_at);
        $log = $instance->workflowLogs()->latest('id')->firstOrFail();
        $this->assertSame('beginnen', $log->transition);
        $this->assertSame('offen', $log->from_place);
        $this->assertSame('in_arbeit', $log->to_place);
    }

    #[Test]
    public function reaching_an_end_place_completes_the_instance_and_blocks_further_transitions(): void
    {
        $this->actingAsAdmin();
        $instance = $this->workflowInstance($this->workflowConfig(), 'in_arbeit');

        $this->post(route('workflow.instances.execute-transition', $instance), ['transition' => 'abschliessen'])
            ->assertSessionHas('success');
        $this->assertNotNull($instance->fresh()->completed_at);

        $this->post(route('workflow.instances.execute-transition', $instance), ['transition' => 'beginnen'])
            ->assertSessionHas('error');
        $this->assertSame('erledigt', $instance->fresh()->current_place);
    }

    #[Test]
    public function unknown_or_not_allowed_transitions_are_refused(): void
    {
        $this->actingAsAdmin();
        $instance = $this->workflowInstance($this->workflowConfig(), 'offen');

        $this->post(route('workflow.instances.execute-transition', $instance), ['transition' => 'abschliessen'])
            ->assertSessionHas('error');
        $this->post(route('workflow.instances.execute-transition', $instance), ['transition' => 'zaubern'])
            ->assertSessionHas('error');
        $this->postJson(route('workflow.instances.execute-transition', $instance), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('transition');

        $this->assertSame('offen', $instance->fresh()->current_place);
    }

    #[Test]
    public function a_stored_transition_without_target_is_refused_instead_of_failing(): void
    {
        $this->actingAsAdmin();
        $config = $this->workflowConfig();
        $config['transitions'][] = ['name' => 'ohne_ziel', 'from' => ['offen']];
        $instance = $this->workflowInstance($config, 'offen');

        $this->post(route('workflow.instances.execute-transition', $instance), ['transition' => 'ohne_ziel'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('offen', $instance->fresh()->current_place);
    }
}
