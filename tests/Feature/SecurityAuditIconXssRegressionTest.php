<?php

namespace Tests\Feature;

use Artwork\Core\Validation\Rules\IconNameRule;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Audit 20.10.2026, Paket 4 (H4, N18): Icon-Namen werden als Dateipfad geladen und dürfen deshalb
 * nur aus Buchstaben, Ziffern, Bindestrich und Unterstrich bestehen. Das SVG wird im Frontend ohne
 * innerHTML gerendert, Drucklayout-Notizen ohne v-html.
 */
final class SecurityAuditIconXssRegressionTest extends FeatureTestCase
{
    private const TRAVERSAL = '../../storage/profile-photos/abc.jpg?';

    /**
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function iconNames(): array
    {
        return [
            'export name' => ['IconHome2', true],
            'slug' => ['home-2', true],
            'legacy underscore' => ['icon_home_2', true],
            'empty' => ['', true],
            'traversal' => [self::TRAVERSAL, false],
            'query' => ['home?x=1', false],
            'fragment' => ['home#x', false],
            'dot' => ['home.svg', false],
            'too long' => [str_repeat('a', 65), false],
            'array' => [['home'], false],
        ];
    }

    #[Test]
    #[DataProvider('iconNames')]
    public function the_icon_rule_only_accepts_plain_names(mixed $icon, bool $valid): void
    {
        $validator = Validator::make(['icon' => $icon], ['icon' => [new IconNameRule()]]);

        $this->assertSame($valid, $validator->passes());
    }

    #[Test]
    public function a_project_group_icon_cannot_point_to_another_file(): void
    {
        $this->actingAsAdmin();

        $this->post(route('projects.store'), [
            'name' => 'Gruppe',
            'isGroup' => true,
            'icon' => self::TRAVERSAL,
        ])->assertSessionHasErrors('icon');

        $project = Project::factory()->create(['icon' => 'IconFolder']);

        $this->patch(route('projects.update', $project), [
            'name' => $project->name,
            'icon' => self::TRAVERSAL,
        ])->assertSessionHasErrors('icon');

        $this->assertSame('IconFolder', $project->fresh()->icon);
    }

    #[Test]
    public function crm_contact_type_icons_are_validated(): void
    {
        $this->actingAsAdmin();

        $this->post(route('crm.types.store'), [
            'name' => 'Typ',
            'icon' => self::TRAVERSAL,
        ])->assertSessionHasErrors('icon');
    }

    #[Test]
    public function the_frontend_renders_icons_and_print_notes_without_raw_html(): void
    {
        $iconLoader = file_get_contents(resource_path('js/Composeables/useTablerIcon.js'));
        $this->assertStringNotContainsString('innerHTML', $iconLoader);
        $this->assertStringContainsString('safeSlug(rawSlug)', $iconLoader);

        foreach ([
            'js/Pages/Projects/ProjectPrintLayoutWindow.vue',
            'js/Pages/Settings/ProjectPrintLayout/Components/SingleProjectPrintLayout.vue',
        ] as $view) {
            $this->assertStringNotContainsString('v-html', file_get_contents(resource_path($view)), $view);
        }
    }
}
