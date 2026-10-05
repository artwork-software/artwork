<?php

namespace Artwork\Modules\Room\Http\Resources;

use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Room
 */
class RoomIndexWithoutEventsResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        // Room::$with lädt admins + creator bereits eager; vorher pro Raum eine Query für
        // created_by und zweimal users()->get(), deren Treffer über UserIndexResource je
        // Person nochmals Schichten/Gewerke nachluden (Benachrichtigungen: 734 Queries).
        // Konsumenten lesen von Admins nur die id (Raumadmin-Check, Anfrage-Dialog,
        // RoomSidenav-Avatare) und vom Ersteller Name/Avatar (Papierkorb).
        $admins = $this->admins->map(static fn (User $user): array => self::slimUser($user))->values()->all();

        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'temporary' => (bool) $this->temporary,
            'start_date' => $this->start_date?->format('d.m.Y'),
            'end_date' => $this->end_date?->format('d.m.Y'),
            'created_at' => $this->created_at?->format('d.m.Y, H:i'),
            'created_by' => $this->creator !== null ? self::slimUser($this->creator) : null,
            'room_admins' => $admins,
            'admins' => $admins,
        ];
    }

    /**
     * @return array{id: int, first_name: string|null, last_name: string|null, profile_photo_url: string}
     */
    private static function slimUser(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }
}
