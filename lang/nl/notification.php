<?php

return [
    'project' => [
        'member' => [
            'add' => 'Je bent toegevoegd aan :project',
            'remove' => 'Je bent verwijderd uit :project',
        ],
        'leader' => [
            'add' => 'Je bent aangewezen als projectleider van :project',
            'remove' => 'Je bent niet langer projectleider van :project',
        ],
        'budget' => [
            'add' => 'Je hebt toegang gekregen tot de begroting van :project',
            'remove' => 'Je toegang tot de begroting van :project is ingetrokken',
            'new_verify_request' => 'Nieuw verificatieverzoek',
            'delete_verify_request' => 'Verificatieverzoek verwijderd',
            'verify_removed' => 'Verificatie in de begroting ingetrokken',
            'fixed' => 'Begroting definitief gemaakt',
            'unfixed' => 'Definitieve vaststelling van de begroting ingetrokken',
        ],
        'key_visual' => [
            'width' => 'De keyvisual moet minimaal 1080 pixels breed zijn.',
        ],
        'file' => [
            'permission_add' => 'Er is een document met je gedeeld',
            'changed' => 'Er is een document gewijzigd',
            'deleted' => 'Er is een document verwijderd'
        ],
        'delete' => ':project is verwijderd',
        'delete_with_events' => ':project met :count activiteiten is verwijderd',
    ],
    'contract' => [
        'add' => 'Er is een contract met je gedeeld',
        'delete' => 'Er is een contract verwijderd',
        'task_assigned' => 'Nieuwe contracttaak voor jou: :task',
        'task_contract' => 'Contract: :contract',
        'task_deadline' => 'Deadline: :date',
    ],
    'project_assignment' => [
        'dissolved_by_free_day' =>
            'Projecttoewijzing van :workerName voor :projectName vervallen door een geregistreerde vrije dag',
        'dissolved_by_reschedule' =>
            'Projecttoewijzing van :workerName voor :projectName vervallen doordat het project is verplaatst',
        'person_assigned' => 'Nieuwe projecttoewijzing: :projectName',
        'person_assigned_bundled' => ':count nieuwe projecttoewijzingen',
        'person_removed' => 'Projecttoewijzing verwijderd: :projectName',
        'person_removed_bundled' => ':count projecttoewijzingen verwijderd',
        'person_wish_accepted' => 'Projectvoorkeur geaccepteerd: :projectName',
        'person_wish_accepted_bundled' => ':count projectvoorkeuren geaccepteerd',
        'removed_reason_reschedule' => 'Verplaatsing',
    ],
    'shift' => [
        'without_event' => 'Dienst zonder activiteit',
        'without_project' => 'Dienst zonder project',
        'add' => 'Je bent toegevoegd aan :shift',
        'remove' => 'Je bent verwijderd uit :shift',
        'locked' => 'Dienstenrooster definitief gemaakt',
        'short_break' => 'Onvoldoende rusttijd',
        'locked_changes' => ':craftAbbreviation · :projectName: dienst gewijzigd ondanks definitieve vaststelling',
        'new_shift_add' => ':craftAbbreviation · :craftName: je bent ingedeeld voor een dienst',
        'conflict' => 'Conflict met je dienst',
        'conflict_text' => ':username heeft je op :date van :from tot :to ingepland, in afwijking van je oorspronkelijke opgave.',
        'conflict_text_committed' =>
            'Je bent op :date van :from tot :to ingepland, in afwijking van je oorspronkelijke opgave. ' .
            'Wie de toewijzing heeft gedaan, is niet geregistreerd. Het dienstenrooster is definitief gemaakt door :username.',
        'conflict_text_unknown' =>
            'Je bent op :date van :from tot :to ingepland, in afwijking van je oorspronkelijke opgave. ' .
            'Wie de toewijzing heeft gedaan, is niet geregistreerd.',
        'deleted_where_locked' => ':craftAbbreviation · :projectName: dienst verwijderd ondanks definitieve vaststelling',
        'shift_staffing' => ':craftAbbreviation · :projectName: je bent ingedeeld voor een dienst',
        'conflict_shift' => 'Dienstconflict op :date van :from tot :to',
        'conflict_shift_withName' => 'Dienstconflict op :date bij :craftName (:craftAbbreviation)',
        'your_short_break' => 'Je bent ingepland met onvoldoende rusttijd',
        'worker_short_break' => 'Persoon ingepland met onvoldoende rusttijd',
        'more_than_ten_days' => 'Je bent voor meer dan 10 opeenvolgende dagen ingepland',
        'worker_more_than_ten_days' => 'Persoon ingepland voor meer dan 10 opeenvolgende dagen',
        'shift_staffing_deleted' => ':craftAbbreviation · :projectName: je bent uit een dienst verwijderd',
        // 'open_demand' => 'Der Termin :event hat noch :count offene Stellen',
        'open_demand' => 'Voor de activiteit :event zijn nog :count plekken onbezet',
        // open_demand_description' =>
        // 'Der Termin :event hat noch :count offene Stellen für die Gewerk :craft :shift_start - :shift_end',
        'open_demand_description' =>
            'Voor de activiteit :event zijn nog :count plekken onbezet voor het vakgebied :craft, dienst :shift',
        'worktime-request' => [
            'new-request' => 'Nieuw verzoek tot wijziging van werktijden',
            'link-to-request' => 'Link naar verzoek',
            'old-new-time' =>
                'De aangevraagde wijziging van de werktijden voor :user: ' .
                'van :start_time naar :end_time.',
        ],
        'new_commit_request_title' => 'Nieuw verzoek voor dienstenplanning',
        'new_commit_request' => 'Nieuw verzoek voor dienstenplanning :craft (week :week, :start_time – :end_time) van :user',
        'link_label_new_commit_request' => 'Naar verzoek',
        'commit_request_approved' => 'Verzoek voor dienstenplanning :craft (week :week) is goedgekeurd',
        'commit_request_rejected' => 'Verzoek voor dienstenplanning :craft (week :week) is afgewezen',
        'commit_request_comment' => 'Opmerking van :user: :comment',
        'commit_request_deadline_title' => 'Deadline voor verzoek dienstenplanning :craft (week :week) verstreken',
        'commit_request_deadline' =>
            'De termijn van :days dagen voor het indienen van de dienstenplanning voor week :week ' .
            '(:start_time – :end_time) voor vakgebied :craft is verstreken. ' .
            'Dien een verzoek voor de dienstenplanning in.',
        'link_label_commit_request_deadline' => 'Naar dienstenplanning',
        // Commit (workflow approval, direct commit, bulk) — sent to every person with a shift
        'locked_craft_week' => 'Je dienstenrooster :craft voor week :week/:year is definitief gemaakt',
        'link_label_own_operation_plan' => 'Naar mijn inzetplanning',
        'link_label_shift_plan' => 'Naar dienstenplanning',
        'commit_request_rejected_rollback' =>
            'De diensten uit dit verzoek zijn teruggezet naar de situatie van vóór het verzoek.',
        'confirmation_accepted' => ':workerName heeft de dienst :craftAbbreviation op :date geaccepteerd',
        'confirmation_declined' => ':workerName heeft de dienst :craftAbbreviation op :date geweigerd',
        'confirmation_comment' => 'Opmerking: :comment',
        'confirmation_by_proxy' => 'Geregistreerd door :userName',
    ],
    'event' => [
        'conflict' => 'Activiteitsconflict',
        'conflict_text' => 'Conflicterende activiteit ingepland: :date_time',
        'new_room_request' => 'Nieuwe ruimteaanvraag',
        'admin_message' => 'Bericht van ruimtebeheerder',
        'room_request_with_changed_room' => 'Ruimteaanvraag met ruimtewijziging bevestigd',
        'room_change_confirmed' => 'Ruimtewijziging bevestigd',
        'new_message' => 'Nieuw bericht over ruimteaanvraag',
        'room_request_accept' => 'Ruimteaanvraag bevestigd',
        'room_request_declined' => 'Ruimteaanvraag afgewezen',
        'deleted' => 'Activiteit geannuleerd'
    ],
    'ai_assistant' => [
        'created' => '{1} Activiteit aangemaakt (via assistent)|[2,*] :count activiteiten aangemaakt (via assistent)',
        'updated' => '{1} Activiteit gewijzigd (via assistent)|[2,*] :count activiteiten gewijzigd (via assistent)',
        'deleted' => '{1} Activiteit verwijderd (via assistent)|[2,*] :count activiteiten verwijderd (via assistent)',
        'reverted' => '{1} Activiteit ongedaan gemaakt (via assistent)|[2,*] :count activiteiten ongedaan gemaakt (via assistent)',
    ],
    'moneySource' => [
        'add_permission' => 'Je hebt toegang gekregen tot :moneySource',
        'remove_permission' => 'Je toegang tot :moneySource is ingetrokken',
        'deleted' => 'Financieringsbron of -groep :moneySource is verwijderd'
    ],
    'scheduling' => [
        'deadline_tomorrow' => 'De deadline van :checklist is morgen',
        'deadline_over' => 'De deadline van :checklist is verstreken',
        'new_tasks' => ':count nieuwe taken voor jou in: :checklists',
        'changes_project' => 'Er zijn wijzigingen in :project',
        'changes_task' => 'Wijzigingen in :task',
        'changes_room' => 'Wijzigingen in :room',
        'changes_event' => 'Activiteit gewijzigd',
        'public_changes_project' => 'Voor publiciteit relevante wijzigingen in :project',
        'changes_vacation' => 'Beschikbaarheid gewijzigd'
    ],
    'keyWords' => [
        'concerns' => 'Betreft:',
        'concerns_shift' => 'Betreft dienst:',
        'your_shift' => 'Jouw dienst:',
        'not_available' => ':username is niet beschikbaar',
        'time_room' => 'Periode:',
        'time_period' => 'Periode: :from tot :to',
        'concerns_time_period' => 'Betreft periode: :start - :end',
    ],
    'department' => [
        'delete' => 'Team :department is verwijderd',
        'add' => 'Je bent toegevoegd aan team :department',
        'remove' => 'Je bent verwijderd uit team :department',
    ],
    'room' => [
        'leader' => [
            'add' => 'Je bent aangewezen als ruimtebeheerder van :room',
            'remove' => 'Je bent niet langer ruimtebeheerder van :room'
        ],
    ],
    'request-verification' => [
        'new' => 'Nieuwe aanvraag voor geplande activiteit',
        'open-index' => 'Aanvragen voor planningsactiviteiten openen',
        'approved-finished' => 'Aanvraag voor planningsactiviteit goedgekeurd en afgerond',
        'approved-finished-by' => 'Aanvraag voor planningsactiviteit goedgekeurd en afgerond door :name',
        'user-approved' => 'Aanvraag voor planningsactiviteit goedgekeurd door :name',
        'event-verification-description' => 'De geplande activiteit ":event" is goedgekeurd',
        'user-rejected' => 'Aanvraag voor planningsactiviteit afgewezen door :name',
    ],
    'inventory_article_changed_title' => 'Voorraadartikel gewijzigd: :articleName',
    'inventory_article_changed_description' => 'Het artikel ":articleName" of een van de statuswaarden ervan is gewijzigd.',
    'inventory_article_overbooked_title' => 'Artikel ":articleName" in je materiaaluitgifte ":issueName" is nu overboekt',
    'inventory_article_overbooked_description' => 'Het artikel ":articleName" in je materiaaluitgifte ":issueName" is nu overboekt.',
    'material_issue' => 'Materiaaluitgifte: :issueName',
    'material' => [
        'return_due_title' => 'Retourdatum bereikt: :issueName',
        'return_due_description' => 'De externe materiaaluitgifte aan ":externalName" had op :date retour moeten zijn.'
            . 'Please confirm the return or report that the material has not been returned yet.',
    ],
];
