<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftTimePreset;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserWorkTimePattern;
use Illuminate\Support\Collection;

/**
 * Memoisierter Lookup auf die (teils vorbestehenden, teils geseedeten)
 * Stammdaten. Alle Demo-Seeder greifen hierüber zu, damit Bestandsdaten
 * einbezogen und Queries nicht wiederholt werden.
 */
final class DemoContext
{
    /** @var array<string, mixed> */
    private array $cache = [];

    public function forget(string ...$keys): void
    {
        foreach ($keys === [] ? array_keys($this->cache) : $keys as $key) {
            unset($this->cache[$key]);
        }
    }

    private function remember(string $key, callable $resolver): mixed
    {
        return $this->cache[$key] ??= $resolver();
    }

    /** @return Collection<int, Craft> */
    public function crafts(): Collection
    {
        return $this->remember('crafts', static fn () => Craft::all());
    }

    public function craftByName(string $name): ?Craft
    {
        return $this->crafts()->firstWhere('name', $name);
    }

    public function craft(string $poolKey): ?Craft
    {
        return $this->craftByName(DemoDataPools::CRAFTS[$poolKey]['name']);
    }

    /** @return Collection<int, Craft> */
    public function universalCrafts(): Collection
    {
        return $this->crafts()->where('universally_applicable', true)->values();
    }

    /** @return Collection<int, ShiftQualification> */
    public function qualifications(): Collection
    {
        return $this->remember('qualifications', static fn () => ShiftQualification::all());
    }

    public function qualification(string $poolKey): ?ShiftQualification
    {
        return $this->qualifications()->firstWhere('name', DemoDataPools::QUALIFICATIONS[$poolKey]['name']);
    }

    /** @return Collection<int, Room> */
    public function rooms(): Collection
    {
        return $this->remember('rooms', static fn () => Room::all());
    }

    public function roomByName(string $name): ?Room
    {
        return $this->rooms()->firstWhere('name', $name);
    }

    /**
     * Räume nach Rolle der Demo-Pools (main_stage, rehearsal, ...). Fällt auf
     * alle Räume zurück, wenn kein Pool-Raum mit dieser Rolle existiert.
     *
     * @return Collection<int, Room>
     */
    public function roomsByRole(string $role): Collection
    {
        $names = collect(DemoDataPools::ROOMS)->where('role', $role)->pluck('name');
        $rooms = $this->rooms()->whereIn('name', $names)->values();

        return $rooms->isNotEmpty() ? $rooms : $this->rooms()->values();
    }

    /** @return Collection<int, EventType> */
    public function eventTypes(): Collection
    {
        return $this->remember('eventTypes', static fn () => EventType::all());
    }

    public function eventType(string $poolKey): ?EventType
    {
        return $this->eventTypes()->firstWhere('name', DemoDataPools::EVENT_TYPES[$poolKey]['name']);
    }

    /** @return Collection<int, EventStatus> */
    public function eventStatuses(): Collection
    {
        return $this->remember('eventStatuses', static fn () => EventStatus::all());
    }

    public function eventStatus(string $name): ?EventStatus
    {
        return $this->eventStatuses()->firstWhere('name', $name);
    }

    /** @return Collection<int, ProjectState> */
    public function projectStates(): Collection
    {
        return $this->remember('projectStates', static fn () => ProjectState::all());
    }

    public function projectState(string $name): ?ProjectState
    {
        return $this->projectStates()->firstWhere('name', $name);
    }

    public function contract(string $poolKey): ?UserContract
    {
        return $this->remember('contracts', static fn () => UserContract::all())
            ->firstWhere('name', DemoDataPools::CONTRACTS[$poolKey]['name']);
    }

    public function workTimePattern(string $poolKey): ?UserWorkTimePattern
    {
        return $this->remember('workTimePatterns', static fn () => UserWorkTimePattern::all())
            ->firstWhere('name', DemoDataPools::WORK_TIME_PATTERNS[$poolKey]['name']);
    }

    public function shiftTimePreset(string $name): ?ShiftTimePreset
    {
        return $this->remember('shiftTimePresets', static fn () => ShiftTimePreset::all())
            ->firstWhere('name', $name);
    }

    public function shiftGroup(string $name): ?ShiftGroup
    {
        return $this->remember('shiftGroups', static fn () => ShiftGroup::all())
            ->firstWhere('name', $name);
    }

    /* -----------------------------------------------------------------
     | Personen
     | ----------------------------------------------------------------- */

    /** @return Collection<int, User> */
    public function users(): Collection
    {
        return $this->remember(
            'users',
            static fn () => User::query()
                ->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo'])
                ->get()
        );
    }

    public function userByEmail(string $email): ?User
    {
        return $this->users()->firstWhere('email', $email);
    }

    public function demoUser(string $first, string $last): ?User
    {
        return $this->userByEmail(DemoDataPools::email($first, $last));
    }

    /** @return Collection<int, User> */
    public function demoUsers(): Collection
    {
        return $this->users()
            ->filter(static fn (User $user) => str_ends_with((string) $user->email, '@' . DemoDataPools::EMAIL_DOMAIN))
            ->values();
    }

    /** @return Collection<int, Freelancer> */
    public function freelancers(): Collection
    {
        return $this->remember('freelancers', static fn () => Freelancer::all());
    }

    /** @return Collection<int, ServiceProvider> */
    public function serviceProviders(): Collection
    {
        return $this->remember('serviceProviders', static fn () => ServiceProvider::all());
    }

    /** Ein User für created_by/assigned_by-Felder: bevorzugt Demo-Dispo, sonst erster User. */
    public function plannerUser(): User
    {
        return $this->remember('plannerUser', function () {
            foreach (DemoDataPools::USERS as $entry) {
                if (($entry['planner'] ?? false) === true) {
                    $user = $this->demoUser($entry['first'], $entry['last']);
                    if ($user !== null) {
                        return $user;
                    }
                }
            }

            return $this->adminUser();
        });
    }

    /** Erster (Alt-)Admin bzw. schlicht der erste User als Fallback-Eigentümer. */
    public function adminUser(): User
    {
        return $this->remember('adminUser', function () {
            foreach (DemoDataPools::USERS as $entry) {
                if (($entry['admin'] ?? false) === true) {
                    $user = $this->demoUser($entry['first'], $entry['last']);
                    if ($user !== null) {
                        return $user;
                    }
                }
            }

            return User::query()->orderBy('id')->firstOrFail();
        });
    }
}
