<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessNotificationRecipient;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Models\User;
use DomainException;

class ExternalAccessNotificationRecipientService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function indexForUi(): array
    {
        return ExternalAccessNotificationRecipient::query()
            ->with(['recipient', 'createdBy:id,first_name,last_name'])
            ->orderBy('recipient_type')
            ->get()
            ->map(fn (ExternalAccessNotificationRecipient $entry) => [
                'id' => $entry->id,
                'recipient_type' => class_basename($entry->recipient_type),
                'recipient_id' => $entry->recipient_id,
                'label' => $this->labelFor($entry),
                'notification_types' => $entry->notification_types,
                'created_by' => $entry->createdBy?->only(['id', 'first_name', 'last_name']),
            ])
            ->all();
    }

    /**
     * @return array{users: array<int, array<string, mixed>>, departments: array<int, array<string, mixed>>}
     */
    public function resolveOptionsForUi(): array
    {
        $users = User::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn (User $u) => [
                'type' => 'User',
                'id' => $u->id,
                'label' => trim($u->first_name . ' ' . $u->last_name) . ' (' . $u->email . ')',
            ])->all();

        $departments = Department::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Department $d) => [
                'type' => 'Department',
                'id' => $d->id,
                'label' => $d->name . ' (' . __('Department') . ')',
            ])->all();

        return ['users' => $users, 'departments' => $departments];
    }

    /**
     * @param list<string> $notificationTypes
     */
    public function addRecipient(
        string $type,
        int $id,
        array $notificationTypes,
        User $createdBy,
    ): ExternalAccessNotificationRecipient {
        $modelClass = $this->resolveRecipientModelClass($type);
        $modelClass::query()->findOrFail($id);
        $this->guardNotificationTypes($notificationTypes);

        return ExternalAccessNotificationRecipient::create([
            'recipient_type' => $modelClass,
            'recipient_id' => $id,
            'notification_types' => array_values($notificationTypes),
            'created_by_user_id' => $createdBy->id,
        ]);
    }

    /**
     * @param list<string> $notificationTypes
     */
    public function updateRecipient(ExternalAccessNotificationRecipient $recipient, array $notificationTypes): void
    {
        $this->guardNotificationTypes($notificationTypes);
        $recipient->update(['notification_types' => array_values($notificationTypes)]);
    }

    public function removeRecipient(ExternalAccessNotificationRecipient $recipient): void
    {
        $recipient->delete();
    }

    /**
     * @param list<string> $notificationTypes
     */
    private function guardNotificationTypes(array $notificationTypes): void
    {
        foreach ($notificationTypes as $nt) {
            if (NotificationEnum::tryFrom($nt) === null) {
                throw new DomainException("Invalid notification type: {$nt}");
            }
        }
    }

    private function resolveRecipientModelClass(string $shortType): string
    {
        return match ($shortType) {
            'User' => User::class,
            'Department' => Department::class,
            default => throw new DomainException("Unknown recipient type: {$shortType}"),
        };
    }

    private function labelFor(ExternalAccessNotificationRecipient $entry): string
    {
        $r = $entry->recipient;
        if ($r === null) {
            return __('Deleted entity');
        }
        if ($r instanceof User) {
            return trim($r->first_name . ' ' . $r->last_name) . ' (' . $r->email . ')';
        }
        if ($r instanceof Department) {
            return $r->name . ' (' . __('Department') . ')';
        }

        return class_basename($r);
    }
}
