<?php

namespace App\Enums;

use App\Notifications\ConflictNotification;
use App\Notifications\DeadlineNotification;
use App\Notifications\EventNotification;
use App\Notifications\ProjectNotification;
use App\Notifications\RoomNotification;
use App\Notifications\RoomRequestNotification;
use App\Notifications\TaskNotification;
use App\Notifications\TeamNotification;

enum NotificationConstEnum: string
{
    case NOTIFICATION_ROOM_REQUEST = 'ROOM_REQUEST';
    case NOTIFICATION_CONFLICT = 'NOTIFICATION_CONFLICT';
    case NOTIFICATION_EVENT_CHANGED = 'NOTIFICATION_EVENT_CHANGED';
    case NOTIFICATION_LOUD_ADJOINING_EVENT = 'NOTIFICATION_LOUD_ADJOINING_EVENT';

    case NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED = 'NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED';
    case NOTIFICATION_BUDGET_STATE_CHANGED = 'NOTIFICATION_BUDGET_STATE_CHANGED';
    case NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED = 'NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED';
    case NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED = 'NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED';

    case NOTIFICATION_UPSERT_ROOM_REQUEST = 'NOTIFICATION_UPSERT_ROOM_REQUEST';
    case NOTIFICATION_REMINDER_ROOM_REQUEST = 'NOTIFICATION_REMINDER_ROOM_REQUEST';
    case NOTIFICATION_ROOM_CHANGED = 'NOTIFICATION_ROOM_CHANGED';
    case NOTIFICATION_ROOM_ANSWER = 'NOTIFICATION_ROOM_ANSWER';

    case NOTIFICATION_NEW_TASK = 'NOTIFICATION_NEW_TASK';
    case NOTIFICATION_TASK_REMINDER = 'TASK_REMINDER';
    case NOTIFICATION_TASK_CHANGED = 'TASK_CHANGED';

    case NOTIFICATION_PROJECT = 'NOTIFICATION_PROJECT';
    case NOTIFICATION_PUBLIC_RELEVANT = 'NOTIFICATION_PUBLIC_RELEVANT';
    case NOTIFICATION_TEAM = 'NOTIFICATION_TEAM';



    public function groupType(): string
    {
        return match ($this) {
            self::NOTIFICATION_ROOM_REQUEST,
            self::NOTIFICATION_CONFLICT,
            self::NOTIFICATION_EVENT_CHANGED,
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => "EVENTS",

            self::NOTIFICATION_BUDGET_STATE_CHANGED,
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED,
            self::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED,
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED => "BUDGET",

            self::NOTIFICATION_UPSERT_ROOM_REQUEST,
            self::NOTIFICATION_REMINDER_ROOM_REQUEST,
            self::NOTIFICATION_ROOM_ANSWER,
            self::NOTIFICATION_ROOM_CHANGED => "ROOMS",

            self::NOTIFICATION_NEW_TASK,
            self::NOTIFICATION_TASK_REMINDER,
            self::NOTIFICATION_TASK_CHANGED => "TASKS",

            self::NOTIFICATION_PROJECT,
            self::NOTIFICATION_PUBLIC_RELEVANT,
            self::NOTIFICATION_TEAM => "PROJECTS",
        };
    }

    public function notificationClass(): string
    {
        return match ($this) {
            self::NOTIFICATION_EVENT_CHANGED => EventNotification::class,

            self::NOTIFICATION_UPSERT_ROOM_REQUEST,
            self::NOTIFICATION_ROOM_REQUEST => RoomRequestNotification::class,

            self::NOTIFICATION_CONFLICT,
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => ConflictNotification::class,

            self::NOTIFICATION_REMINDER_ROOM_REQUEST,
            self::NOTIFICATION_ROOM_ANSWER,
            self::NOTIFICATION_ROOM_CHANGED => RoomNotification::class,

            self::NOTIFICATION_TASK_REMINDER => DeadlineNotification::class,
            self::NOTIFICATION_NEW_TASK,
            self::NOTIFICATION_TASK_CHANGED => TaskNotification::class,

            self::NOTIFICATION_PROJECT => ProjectNotification::class,
            self::NOTIFICATION_TEAM => TeamNotification::class,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::NOTIFICATION_ROOM_ANSWER,
            self::NOTIFICATION_ROOM_REQUEST => "Raumanfragen bestätigt oder abgelehnt",
            self::NOTIFICATION_CONFLICT => "Terminkonflikte",
            self::NOTIFICATION_EVENT_CHANGED => "Terminänderung",
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => "Nebenveranstaltungen",

            self::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED => 'Budget- und Finanzierungsquellenzugriff',
            self::NOTIFICATION_BUDGET_STATE_CHANGED => 'Änderungen am Budgetstatus',
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED => 'Änderungen an Budget und Finanzierungsquellen',
            self::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED => 'Änderungen an Dokumenten und Verträgen',

            self::NOTIFICATION_UPSERT_ROOM_REQUEST => "Neue/geänderte Raumanfrage",
            self::NOTIFICATION_REMINDER_ROOM_REQUEST => "Erinnerung Raumanfragen",
            self::NOTIFICATION_ROOM_CHANGED => "Änderungen an Raum",

            self::NOTIFICATION_NEW_TASK => "Neue Aufgaben",
            self::NOTIFICATION_TASK_REMINDER => "Erinnerung an Aufgaben",
            self::NOTIFICATION_TASK_CHANGED => "Änderungen an Aufgaben",

            self::NOTIFICATION_PROJECT => "Änderungen in Projekten & Projektgruppen",
            self::NOTIFICATION_PUBLIC_RELEVANT => 'Öffentlichkeitsarbeitsrelevante Änderungen',
            self::NOTIFICATION_TEAM => "Teamzugehörigkeit",
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NOTIFICATION_ROOM_ANSWER,
            self::NOTIFICATION_ROOM_REQUEST => "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
            self::NOTIFICATION_CONFLICT => "Werde benachrichtigt, sobald jemand einen Termin einstellt, welcher mit einem deiner Termine kollidiert.",
            self::NOTIFICATION_EVENT_CHANGED => "Erfahre, ob es Änderungen an deinen Terminen gibt oder ein Termin abgesagt wurde.",
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => "Erfahre, ob parallel zu einem deiner Termine in einem Nebenraum laute oder Termine mit Publikum eingestellt wurden.",

            self::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED => 'Werde benachrichtigt wenn sich dein Zugriff auf Projektbudgets oder Finanzierungsquellen geändert hat.',
            self::NOTIFICATION_BUDGET_STATE_CHANGED => 'Werde benachrichtigt wenn Teile deiner Kalkulation festgeschrieben, zur Verifizierung angefragt oder verifiziert wurden.',
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED => 'Werde benachrichtigt wenn es Änderungen am Budget, dem Kostenträger oder Urheberrechten deines Projektes gab. Erhalte außerdem eine Warnung, sobald deine Finanzierungsquelle ins Minus gerutscht ist.',
            self::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED => 'Erfahre ob du eine Freigabe für Dokumente oder Verträge erhalten hast und ob es Änderungen an diesen Dokumenten gab.',

            self::NOTIFICATION_UPSERT_ROOM_REQUEST => "Erfahre ob es neue oder geänderte Raumanfragen gibt.",
            self::NOTIFICATION_REMINDER_ROOM_REQUEST => "Lass’ dich erinnern, wenn Raumanfragen dringend werden.",
            self::NOTIFICATION_ROOM_CHANGED => "Werde benachrichtigt, sobald es Änderungen an deinen Räumen oder deinen Raum-Zuständigkeiten gibt.",

            self::NOTIFICATION_NEW_TASK => "Erfahre ob es neue Aufgaben für dich oder dein Team gibt.",
            self::NOTIFICATION_TASK_REMINDER => "Lass dich erinnern, wenn Aufgaben dringend werden oder bereits ihre Deadline überschritten haben.",
            self::NOTIFICATION_TASK_CHANGED => "Erfahre ob es Änderungen an deinen Aufgaben gibt",

            self::NOTIFICATION_PROJECT => "Erfahre ob es Änderungen in deinen Projekten oder -gruppen gibt und welche Rolle du im Projektteam hast.",
            self::NOTIFICATION_PUBLIC_RELEVANT => 'Werde benachrichtigt, sobald es Änderungen an deinen Projekten gibt, die sich auf die Öffentlichkeitsarbeit auswirken können.',
            self::NOTIFICATION_TEAM => "Werde benachrichtigt, sobald sich deine Teamzugehörigkeit ändert.",
        };
    }
}
