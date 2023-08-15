<?php

namespace App\Enums;

enum NotificationGroupEnum: string
{
    case EVENTS = 'EVENTS';
    case BUDGET = 'BUDGET';
    case ROOMS = 'ROOMS';
    case TASKS = 'TASKS';
    case PROJECTS = 'PROJECTS';

    case SHIFTS = 'SHIFTS';


    public function title(): string
    {
        return match ($this) {
            self::EVENTS => "Raumbelegungen & Termine",
            self::BUDGET => "Projektbudgets & Finanzierungsquellen",
            self::ROOMS => "Räume & Raumbelegungsanfragen",
            self::TASKS => "Aufgaben & Checklisten",
            self::PROJECTS => "Projekte & Teams",
            self::SHIFTS => "Schichtplanung",
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EVENTS => "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden, es Änderungen an deinen Terminen gibt und mehr.",
            self::BUDGET => "Erfahre welchen Stand deine Budgetkalkulation hat, welche Dokumente für dich freigegeben wurden, ob deine Finanzierungsquelle ins Minus gerutscht ist und mehr.",
            self::ROOMS => "Erfahre ob es neue Belegungsanfragen für die von dir verwalteten Räume gibt. Lass’ dich außerdem an die Bearbeitung dringender Anfragen erinnern und mehr.",
            self::TASKS => "Erfahre ob es neue Aufgaben für dich und dein Team gibt. Lass dich außerdem an die Erledigung dringender Aufgaben erinnern und mehr",
            self::PROJECTS => "Erfahre ob es Änderungen an deinen Projekten gibt, du zu einem neuen Projekt oder Team hinzugefügt wurdest und mehr.",
            self::SHIFTS => "Erfahre ob Schichten geändert wurden, es Konflikte in der Schichtplanung gab, Mitarbeiter*innen ersetzt werden müssen und mehr.",
        };
    }

}
