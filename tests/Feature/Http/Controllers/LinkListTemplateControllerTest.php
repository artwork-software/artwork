<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\LinkListTemplate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class LinkListTemplateControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_link_list_templates(): void
    {
        $this->get(route('link_list_templates.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_link_list_templates(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('link_list_templates.index'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_store_link_list_template(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('link_list_templates.store'), [
            'name' => 'My Template',
            'entries' => [['display' => 'Google', 'url' => 'https://google.com']],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('link_list_templates', ['name' => 'My Template']);
    }

    #[Test]
    public function store_validates_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(
            route('link_list_templates.store'),
            ['entries' => [['display' => 'A']]],
            ['Accept' => 'application/json']
        );

        $response->assertUnprocessable();
    }

    #[Test]
    public function admin_can_destroy_link_list_template(): void
    {
        $this->actingAsAdmin();
        $template = LinkListTemplate::create([
            'name' => 'X',
            'entries' => [['display' => 'Foo']],
            'created_by' => $this->actingAsAdmin()->id,
        ]);

        $response = $this->delete(route('link_list_templates.destroy', $template));

        $response->assertNoContent();
        $this->assertDatabaseMissing('link_list_templates', ['id' => $template->id]);
    }
}
