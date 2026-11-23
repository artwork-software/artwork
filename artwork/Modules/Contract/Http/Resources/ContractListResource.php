<?php

namespace Artwork\Modules\Contract\Http\Resources;

use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Schlanke Zeile der Vertragsübersicht. Alles, was nur das Bearbeiten-Fenster braucht (Kommentare,
 * Aufgaben, vollständige Personendaten), liefert ContractResource erst beim Öffnen eines Vertrags.
 *
 * Erwartet vorab geladen: project.users, contract_type, company_type, currency, accessingUsers,
 * accessingDepartments, tasks.
 *
 * @mixin Contract
 */
class ContractListResource extends JsonResource
{
    /**
     * Dateitypen, die der Browser gefahrlos direkt anzeigen kann (kein HTML, kein SVG).
     */
    public const PREVIEWABLE_EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp'];

    public static function isPreviewable(Contract $contract): bool
    {
        return in_array(
            strtolower(pathinfo((string) $contract->basename, PATHINFO_EXTENSION)),
            self::PREVIEWABLE_EXTENSIONS,
            true
        );
    }

    public static function previewKind(Contract $contract): ?string
    {
        if (!self::isPreviewable($contract)) {
            return null;
        }

        return strtolower(pathinfo((string) $contract->basename, PATHINFO_EXTENSION)) === 'pdf' ? 'pdf' : 'image';
    }

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray(Request $request): array
    {
        /** @var Contract $contract */
        $contract = $this->resource;
        $accessibleUsers = collect($contract->accessingUsers->all());

        return [
            'id' => $contract->id,
            'name' => $contract->name,
            'partner' => $contract->contract_partner,
            'project' => $contract->project
                ? ['id' => $contract->project->id, 'name' => $contract->project->name]
                : null,
            'amount' => $contract->amount,
            'currency' => $contract->currency
                ? ['id' => $contract->currency->id, 'name' => $contract->currency->name]
                : null,
            'contract_type' => $contract->contract_type?->only(['id', 'name', 'color']),
            'company_type' => $contract->company_type?->only(['id', 'name', 'color']),
            'ksk_liable' => $contract->ksk_liable,
            'foreign_tax' => $contract->foreign_tax,
            // Kalenderdatum (Y-m-d) – ein Carbon-Objekt würde als UTC-Zeitpunkt des Vortags serialisiert
            'deadline_date' => $contract->deadline_date?->format('Y-m-d'),
            'description' => $contract->description,
            'contract_state' => $contract->contract_state,
            'contract_state_comment' => $contract->contract_state_comment,
            'is_previewable' => self::isPreviewable($contract),
            // 'pdf' oder 'image': bestimmt, wie das Vorschau-Fenster die Datei einbettet
            'preview_kind' => self::previewKind($contract),
            'open_tasks_count' => $contract->tasks
                ->filter(static fn (Task $task): bool => !$task->done)
                ->count(),
            // Gespeicherte Freigaben
            'accessibleUsers' => $this->slimUsers($accessibleUsers),
            // Anzeige inkl. Projektleitungen, die laut ContractPolicy ohnehin Zugriff haben
            'displayedAccessUsers' => $this->slimUsers($this->withProjectManagers($accessibleUsers)),
            'accessibleDepartments' => $contract->accessingDepartments->map(static fn ($department): array => [
                'id' => $department->id,
                'name' => $department->name,
                'svg_name' => $department->svg_name,
            ]),
        ];
    }

    /**
     * @param Collection<int, User> $users
     * @return Collection<int, User>
     */
    private function withProjectManagers(Collection $users): Collection
    {
        $users = collect($users->all());

        foreach ($this->resource->project?->users ?? [] as $user) {
            if ($user->pivot->is_manager && !$users->contains('id', $user->id)) {
                $users->push($user);
            }
        }

        return $users;
    }

    /**
     * @param Collection<int, User> $users
     * @return array<int, array<string, mixed>>
     */
    private function slimUsers(Collection $users): array
    {
        return $users->map(static fn (User $user): array => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'profile_photo_url' => $user->profile_photo_url,
            'deactivated_at' => User::deactivatedAtFor($user),
        ])->values()->all();
    }
}
