<?php

namespace App\Enums;

enum NotificationConstEnum: string
{
    case NOTIFICATION_ROOM_REQUEST = 'ROOM_REQUEST';
    case NOTIFICATION_CONFLICT = 'NOTIFICATION_CONFLICT';
    case NOTIFICATION_EVENT_CHANGED = 'NOTIFICATION_EVENT_CHANGED';
    case NOTIFICATION_LOUD_ADJOINING_EVENT = 'NOTIFICATION_LOUD_ADJOINING_EVENT';

    case NOTIFICATION_UPSERT_ROOM_REQUEST = 'NOTIFICATION_UPSERT_ROOM_REQUEST';
    case NOTIFICATION_REMINDER_ROOM_REQUEST = 'NOTIFICATION_REMINDER_ROOM_REQUEST';
    case NOTIFICATION_ROOM_CHANGED = 'NOTIFICATION_ROOM_CHANGED';

    case NOTIFICATION_NEW_TASK = 'NOTIFICATION_NEW_TASK';
    case NOTIFICATION_TASK_REMINDER = 'TASK_REMINDER';
    case NOTIFICATION_TASK_CHANGED = 'TASK_CHANGED';

    case NOTIFICATION_PROJECT = 'NOTIFICATION_PROJECT';
    case NOTIFICATION_TEAM = 'NOTIFICATION_TEAM';

    public function groupType(): string
    {
        return match ($this) {
            self::NOTIFICATION_ROOM_REQUEST,
            self::NOTIFICATION_CONFLICT,
            self::NOTIFICATION_EVENT_CHANGED,
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => "events",

            self::NOTIFICATION_UPSERT_ROOM_REQUEST,
            self::NOTIFICATION_REMINDER_ROOM_REQUEST,
            self::NOTIFICATION_ROOM_CHANGED => "rooms",

            self::NOTIFICATION_NEW_TASK,
            self::NOTIFICATION_TASK_REMINDER,
            self::NOTIFICATION_TASK_CHANGED => "tasks",

            self::NOTIFICATION_PROJECT,
            self::NOTIFICATION_TEAM => "projects"
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::NOTIFICATION_ROOM_REQUEST => "Raumanfragen bestätigt oder abgelehnt",
            self::NOTIFICATION_CONFLICT => "Terminkonflikte",
            self::NOTIFICATION_EVENT_CHANGED => "Terminänderung",
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => "Nebenveranstaltungen",

            self::NOTIFICATION_UPSERT_ROOM_REQUEST => "Neue/geänderte Raumanfrage",
            self::NOTIFICATION_REMINDER_ROOM_REQUEST => "Erinnerung Raumanfragen",
            self::NOTIFICATION_ROOM_CHANGED => "Änderungen an Raum",

            self::NOTIFICATION_NEW_TASK => "Neue Aufgaben",
            self::NOTIFICATION_TASK_REMINDER => "Erinnerung an Aufgaben",
            self::NOTIFICATION_TASK_CHANGED => "Änderungen an Aufgaben",

            self::NOTIFICATION_PROJECT => "Änderungen an Projekten",
            self::NOTIFICATION_TEAM => "Teamzugehörigkeit"
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NOTIFICATION_ROOM_REQUEST => "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
            self::NOTIFICATION_CONFLICT => "Werde benachrichtigt, sobald jemand einen Termin einstellt, welcher mit einem deiner Termine kollidiert.",
            self::NOTIFICATION_EVENT_CHANGED => "Erfahre, ob es Änderungen an deinen Terminen gibt oder ein Termin abgesagt wurde.",
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => "Erfahre, ob parallel zu einem deiner Termine in einem Nebenraum laute oder Termine mit Publikum eingestellt wurden.",

            self::NOTIFICATION_UPSERT_ROOM_REQUEST => "Erfahre ob es neue oder geänderte Raumanfragen gibt.",
            self::NOTIFICATION_REMINDER_ROOM_REQUEST => "Lass’ dich erinnern, wenn Raumanfragen dringend werden.",
            self::NOTIFICATION_ROOM_CHANGED => "Werde benachrichtigt, sobald es Änderungen an deinen Räumen oder deinen Raum-Zuständigkeiten gibt.",

            self::NOTIFICATION_NEW_TASK => "Erfahre ob es neue Aufgaben für dich oder dein Team gibt.",
            self::NOTIFICATION_TASK_REMINDER => "Lass dich erinnern, wenn Aufgaben dringend werden oder bereits ihre Deadline überschritten haben.",
            self::NOTIFICATION_TASK_CHANGED => "Erfahre ob es Änderungen an deinen Aufgaben gibt",

            self::NOTIFICATION_PROJECT => "Erfahre ob es Änderungen an deinen Projekten gibt und welche Rolle du im Projektteam hast.",
            self::NOTIFICATION_TEAM => "Werde benachrichtigt, sobald sich deine Teamzugehörigkeit ändert."
        };
    }
}
