<?php

namespace Database\Seeders;

use Artwork\Modules\Workflow\Models\WorkflowDefinition;
use Artwork\Modules\Workflow\Models\WorkflowDefinitionConfig;
use Illuminate\Database\Seeder;

class ShiftViolationWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Create a functional shift violation workflow for the current system
        $workflow = WorkflowDefinition::updateOrCreate(
            [
                'type' => 'shift_violation_management'
            ],
            [
                'name' => 'Shift Rule Violation Management',
                'description' => 'Workflow für die Verwaltung von Schicht-Regelverstößen',
                'is_active' => true,
            ]
        );

        // Create the workflow configuration
        WorkflowDefinitionConfig::updateOrCreate(
            [
                'workflow_definition_id' => $workflow->id
            ],
            [
                'config' => [
                    'initial_place' => 'detected',
                    'places' => [
                        [
                            'name' => 'detected',
                            'type' => 'start',
                            'label' => 'Erkannt',
                            'description' => 'Regelverstoß wurde erkannt und muss bearbeitet werden',
                            'color' => '#fbbf24'
                        ],
                        [
                            'name' => 'in_review',
                            'type' => 'intermediate',
                            'label' => 'In Bearbeitung',
                            'description' => 'Regelverstoß wird gerade bearbeitet',
                            'color' => '#3b82f6'
                        ],
                        [
                            'name' => 'resolved',
                            'type' => 'end',
                            'label' => 'Gelöst',
                            'description' => 'Regelverstoß wurde erfolgreich gelöst',
                            'color' => '#10b981'
                        ],
                        [
                            'name' => 'dismissed',
                            'type' => 'end',
                            'label' => 'Verworfen',
                            'description' => 'Regelverstoß wurde als ungültig verworfen',
                            'color' => '#6b7280'
                        ],
                        [
                            'name' => 'exception_granted',
                            'type' => 'end',
                            'label' => 'Ausnahme gewährt',
                            'description' => 'Ausnahme für diesen Regelverstoß wurde gewährt',
                            'color' => '#8b5cf6'
                        ]
                    ],
                    'transitions' => [
                        [
                            'name' => 'start_review',
                            'label' => 'Bearbeitung starten',
                            'from' => ['detected'],
                            'to' => 'in_review',
                            'actions' => [
                                [
                                    'action' => 'update_data',
                                    'parameters' => [
                                        'data' => [
                                            'review_started_at' => '@now',
                                            'review_started_by' => '@current_user_id',
                                            'status' => 'in_review'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'resolve_violation',
                            'label' => 'Verstoß lösen',
                            'from' => ['detected', 'in_review'],
                            'to' => 'resolved',
                            'actions' => [
                                [
                                    'action' => 'shift_rule_notification',
                                    'parameters' => [
                                        'message' => 'Regelverstoß wurde erfolgreich gelöst',
                                        'notification_type' => 'violation_resolved'
                                    ]
                                ],
                                [
                                    'action' => 'update_data',
                                    'parameters' => [
                                        'data' => [
                                            'resolved_at' => '@now',
                                            'resolved_by' => '@current_user_id',
                                            'status' => 'resolved'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'dismiss_violation',
                            'label' => 'Verstoß verwerfen',
                            'from' => ['detected', 'in_review'],
                            'to' => 'dismissed',
                            'actions' => [
                                [
                                    'action' => 'update_data',
                                    'parameters' => [
                                        'data' => [
                                            'dismissed_at' => '@now',
                                            'dismissed_by' => '@current_user_id',
                                            'status' => 'dismissed'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'grant_exception',
                            'label' => 'Ausnahme gewähren',
                            'from' => ['detected', 'in_review'],
                            'to' => 'exception_granted',
                            'actions' => [
                                [
                                    'action' => 'shift_rule_notification',
                                    'parameters' => [
                                        'message' => 'Ausnahme für Regelverstoß wurde gewährt',
                                        'notification_type' => 'exception_granted'
                                    ]
                                ],
                                [
                                    'action' => 'update_data',
                                    'parameters' => [
                                        'data' => [
                                            'exception_granted_at' => '@now',
                                            'exception_granted_by' => '@current_user_id',
                                            'status' => 'exception_granted'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'name' => 'reopen_for_review',
                            'label' => 'Zur erneuten Prüfung öffnen',
                            'from' => ['dismissed'],
                            'to' => 'detected',
                            'actions' => [
                                [
                                    'action' => 'update_data',
                                    'parameters' => [
                                        'data' => [
                                            'reopened_at' => '@now',
                                            'reopened_by' => '@current_user_id',
                                            'status' => 'detected'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'variables' => [
                        '@current_user_id' => [
                            'type' => 'context',
                            'path' => 'auth.user.id'
                        ],
                        '@now' => [
                            'type' => 'timestamp',
                            'format' => 'Y-m-d H:i:s'
                        ]
                    ]
                ]
            ]
        );
    }
}
