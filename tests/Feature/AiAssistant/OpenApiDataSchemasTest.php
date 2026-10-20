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
            '/api/v1/ai/rooms' => 'AiRoomListData',
            '/api/v1/ai/event-types' => 'AiEventTypeListData',
            '/api/v1/ai/actions/{action}' => 'AiPendingActionData',
            '/api/v1/ai/people' => 'AiPersonListData',
            '/api/v1/ai/people/{type}/{id}/workload' => 'AiPersonWorkloadData',
            '/api/v1/ai/events/{event}/shifts' => 'AiEventShiftsData',
            '/api/v1/ai/shifts/open' => 'AiOpenShiftListData',
            '/api/v1/ai/shifts/{shift}/candidates' => 'AiShiftCandidateListData',
            '/api/v1/ai/shift-rule-violations' => 'AiRuleViolationListData',
            '/api/v1/ai/crafts' => 'AiCraftListData',
            '/api/v1/ai/crafts/{craft}/capacity' => 'AiCraftCapacityData',
            '/api/v1/ai/day-overview' => 'AiDayOverviewData',
            '/api/v1/ai/projects/{project}/budget-summary' => 'AiBudgetSummaryData',
            '/api/v1/ai/absences' => 'AiPersonAbsencesListData',
            '/api/v1/ai/me/tasks' => 'AiTaskListData',
            '/api/v1/ai/rooms/conflicts' => 'AiRoomConflictListData',
            '/api/v1/ai/people/{type}/{id}/work-time' => 'AiWorkTimeData',
            '/api/v1/ai/contracts' => 'AiContractListData',
            '/api/v1/ai/work-time/overview' => 'AiWorkTimeOverviewData',
            '/api/v1/ai/rooms/utilization' => 'AiRoomUtilizationListData',
            '/api/v1/ai/checklists' => 'AiChecklistListData',
            '/api/v1/ai/qualifications' => 'AiQualificationListData',
            '/api/v1/ai/project-states' => 'AiProjectStateListData',
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
            '/api/v1/ai/events/batch' => ['post', 'delete', 'patch'],
            '/api/v1/ai/events/{event}' => ['patch'],
            '/api/v1/ai/shift-assignments' => ['post'],
            '/api/v1/ai/shift-assignments/remove' => ['post'],
            '/api/v1/ai/shift-assignments/bulk' => ['post'],
            '/api/v1/ai/tasks/{task}' => ['patch'],
            '/api/v1/ai/shift-assignments/replace' => ['post'],
            '/api/v1/ai/tasks' => ['post'],
            '/api/v1/ai/absences' => ['post'],
            '/api/v1/ai/shifts' => ['post'],
            '/api/v1/ai/shifts/copy' => ['post'],
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

    #[Test]
    public function the_shift_suggestion_is_part_of_the_contract(): void
    {
        $paths = $this->contract()['paths'] ?? [];

        foreach (
            [
                '/api/v1/ai/shift-plan/suggest' => 'AiShiftSuggestionData',
                '/api/v1/ai/shift-plan/staff-event' => 'AiEventStaffingData',
                '/api/v1/ai/people/common-free-slots' => 'AiCommonSlotsData',
                '/api/v1/ai/shift-plan/replacements' => 'AiReplacementSuggestionData',
            ] as $path => $schema
        ) {
            $this->assertSame(
                '#/components/schemas/' . $schema,
                $paths[$path]['post']['responses']['200']['content']['application/json']['schema']['$ref'] ?? '',
                $path
            );
        }
    }

    /**
     * Ein optionales Feld (`sometimes`) darf im Contract nicht als Pflichtfeld stehen.
     *
     * Anlass: scramble markiert ein Objekt als erforderlich, sobald **eine** verschachtelte Regel
     * `required` traegt (`DeepParametersMerger.php:89-93`). `pending_action` war dadurch Pflicht,
     * und ein Fall-A-Turn konnte die exportierte Spec nie erfuellen.
     */
    #[Test]
    public function optional_request_fields_are_not_required_in_the_contract(): void
    {
        $schemas = $this->contract()['components']['schemas'] ?? [];
        $checked = 0;

        foreach (glob(base_path('artwork/Modules/AiAssistant/Http/Requests/*.php')) ?: [] as $file) {
            $class = 'Artwork\\Modules\\AiAssistant\\Http\\Requests\\' . basename($file, '.php');

            if (!class_exists($class) || (new \ReflectionClass($class))->isAbstract()) {
                continue;
            }

            $schema = $schemas[class_basename($class)] ?? null;

            if ($schema === null) {
                continue;
            }

            $rules = (new $class())->rules();
            $checked++;

            foreach ($schema['required'] ?? [] as $field) {
                $ruleSet = $rules[$field] ?? [];
                $ruleSet = is_string($ruleSet) ? explode('|', $ruleSet) : (array) $ruleSet;
                $names = array_map(
                    static fn (mixed $rule): string => is_object($rule) ? $rule::class : (string) $rule,
                    $ruleSet
                );

                if (!in_array('sometimes', $names, true)) {
                    continue;
                }

                $this->assertTrue(
                    in_array('required', $names, true) || in_array('present', $names, true),
                    class_basename($class) . ".{$field} ist 'sometimes', steht im Contract aber unter required."
                );
            }
        }

        $this->assertGreaterThan(0, $checked, 'Kein Request-Schema geprueft — der Test liefe leer.');
    }

    /**
     * Freie Schluessel sind im Contract ein Objekt, keine Liste (Export-Fix 2, 16.09.2026).
     *
     * Anlass: `LaravelDataToSchema` kannte nur `Foo[]`; jedes andere Array wurde zum nackten
     * `ArrayType`, dessen `items` scramble still mit `string` fuellt. `pending_action.args` und
     * `.preview` standen dadurch als `array<string>` im Export, obwohl
     * `contracts/action-contract.md` Objekte verlangt.
     */
    #[Test]
    public function map_shaped_fields_are_objects_in_the_contract(): void
    {
        $schemas = $this->contract()['components']['schemas'] ?? [];

        $expected = [
            'AiPendingActionData.args' => ['AiPendingActionData', 'args'],
            'AiPendingActionData.preview' => ['AiPendingActionData', 'preview'],
            'AiConversationTurnData.context' => ['AiConversationTurnData', 'context'],
        ];

        foreach ($expected as $label => [$schema, $field]) {
            $this->assertSame(
                'object',
                $schemas[$schema]['properties'][$field]['type'] ?? null,
                "{$label} muss ein Objekt sein, keine Liste."
            );
        }

        $this->assertSame(
            'object',
            $schemas['StoreAiConversationTurnRequest']['properties']['pending_action']
                ['properties']['args']['type'] ?? null,
            'StoreAiConversationTurnRequest.pending_action.args muss ein Objekt sein.'
        );
    }

    /**
     * Listen tragen den Elementtyp, den der Konstruktor-Docblock nennt — nicht den
     * `string`-Default von scrambles `ArrayType`.
     */
    #[Test]
    public function typed_lists_carry_their_element_type(): void
    {
        $schemas = $this->contract()['components']['schemas'] ?? [];

        $this->assertSame(
            'integer',
            $schemas['AiWriteResultData']['properties']['ids']['items']['type'] ?? null,
            'AiWriteResultData.ids ist int[] und muss ganzzahlige Elemente tragen.'
        );

        $this->assertSame(
            'object',
            $schemas['AiConversationTurnData']['properties']['tool_calls']['items']['type'] ?? null,
            'AiConversationTurnData.tool_calls ist eine Liste von Objekten.'
        );

        $this->assertSame(
            '#/components/schemas/AiJwkData',
            $schemas['AiJwksData']['properties']['keys']['items']['$ref'] ?? null,
            'AiJwksData.keys verweist auf AiJwkData.'
        );
    }

    /**
     * Antworten mit Datentyp stehen auch dann im Contract, wenn der Status vom Code abhaengt.
     *
     * Anlass: `storeTurn` gab den Status ternaer zurueck; scramble liest ihn statisch und fiel
     * auf `string` zurueck — die Antwort stand ohne Typ im Export.
     */
    #[Test]
    public function the_turn_endpoint_declares_its_response_type(): void
    {
        $responses = $this->contract()['paths']['/api/v1/ai/conversations/{conversation}/turns']
            ['post']['responses'] ?? [];

        foreach (['200', '201'] as $status) {
            $this->assertSame(
                '#/components/schemas/AiConversationTurnData',
                $responses[$status]['content']['application/json']['schema']['$ref'] ?? '',
                "Antwort {$status} von POST …/turns traegt kein Schema."
            );
        }
    }

    /**
     * Ein leeres Array ist an den Stellen, wo JSON Schema ein Schema erwartet, kein gueltiger
     * Wert. Scramble schreibt es, wenn es einen Typ nicht ableiten kann.
     */
    #[Test]
    public function the_ai_schemas_contain_no_empty_schema_fragments(): void
    {
        $schemas = $this->contract()['components']['schemas'] ?? [];
        $found = [];

        foreach ($schemas as $name => $schema) {
            if (!str_starts_with((string) $name, 'Ai') && !str_starts_with((string) $name, 'StoreAi')) {
                continue;
            }

            $this->collectEmptyFragments($schema, (string) $name, $found);
        }

        $this->assertSame([], $found, 'Leere Schema-Fragmente im Contract: ' . implode(', ', $found));
    }

    /**
     * @param mixed $node
     * @param string[] $found
     */
    private function collectEmptyFragments(mixed $node, string $path, array &$found): void
    {
        if (!is_array($node)) {
            return;
        }

        foreach (['items', 'additionalProperties'] as $key) {
            if (array_key_exists($key, $node) && $node[$key] === []) {
                $found[] = $path . '.' . $key;
            }
        }

        foreach ($node as $key => $value) {
            $this->collectEmptyFragments($value, $path . '/' . $key, $found);
        }
    }
}
