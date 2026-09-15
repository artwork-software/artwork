<?php

namespace Tests\Feature\AiAssistant;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-api-context-projects: Die DTO-Schemata im Contract duerfen nicht leer sein (0.4 §5).
 *
 * Geprueft wird die committete `openapi.json` — dass sie aktuell ist, stellt der CI-Job
 * `openapi-contract` sicher (core-openapi-contract).
 */
final class OpenApiDataSchemasTest extends TestCase
{
    /** @return array<string, mixed> */
    private function contract(): array
    {
        $contract = json_decode((string) file_get_contents(base_path('openapi.json')), true);

        $this->assertIsArray($contract);

        return $contract;
    }

    #[Test]
    public function every_data_schema_carries_its_fields(): void
    {
        $schemas = $this->contract()['components']['schemas'] ?? [];

        $this->assertNotEmpty($schemas);

        foreach ($schemas as $name => $schema) {
            if (!str_starts_with((string) $name, 'Ai')) {
                continue;
            }

            $this->assertNotEmpty(
                $schema['properties'] ?? [],
                "Schema {$name} hat keine Felder — scramble hat die Data-Klasse nicht ausgewertet."
            );
        }
    }

    #[Test]
    public function the_read_endpoints_are_part_of_the_contract(): void
    {
        $paths = $this->contract()['paths'] ?? [];

        $expected = [
            '/api/v1/ai/context' => 'AiContextData',
            '/api/v1/ai/projects' => 'AiProjectListData',
            '/api/v1/ai/projects/{project}' => 'AiProjectDetailData',
            '/api/v1/ai/events' => 'AiEventListData',
            '/api/v1/ai/rooms/occupancy' => 'AiRoomOccupancyListData',
            '/api/v1/ai/rooms/free-slots' => 'AiFreeSlotListData',
            '/api/v1/ai/me/schedule' => 'AiScheduleData',
            '/api/v1/ai/shift-plan' => 'AiShiftPlanData',
            '/api/v1/ai/inventory/availability' => 'AiInventoryListData',
            '/api/v1/ai/actions/{action}' => 'AiPendingActionData',
        ];

        foreach ($expected as $path => $schema) {
            $this->assertArrayHasKey($path, $paths, "Pfad {$path} fehlt im Contract");
            $this->assertSame(
                '#/components/schemas/' . $schema,
                $paths[$path]['get']['responses']['200']['content']['application/json']['schema']['$ref'] ?? '',
                "Antwortschema von {$path} fehlt oder zeigt woanders hin"
            );
        }
    }

    #[Test]
    public function the_write_endpoints_offer_preview_and_result(): void
    {
        $paths = $this->contract()['paths'] ?? [];

        $expected = [
            '/api/v1/ai/events/batch' => ['post', 'delete'],
            '/api/v1/ai/events/{event}' => ['patch'],
        ];

        foreach ($expected as $path => $methods) {
            $this->assertArrayHasKey($path, $paths, "Pfad {$path} fehlt im Contract");

            foreach ($methods as $method) {
                $schema = $paths[$path][$method]['responses']['200']['content']['application/json']['schema'] ?? [];
                $refs = array_column($schema['anyOf'] ?? [], '$ref');

                $this->assertContains('#/components/schemas/AiWritePreviewData', $refs, "{$method} {$path}");
                $this->assertContains('#/components/schemas/AiWriteResultData', $refs, "{$method} {$path}");
            }
        }
    }
}
