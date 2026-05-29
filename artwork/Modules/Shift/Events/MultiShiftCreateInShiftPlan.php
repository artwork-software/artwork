<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MultiShiftCreateInShiftPlan implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;


    public Collection $shifts;
    /**
     * Create a new event instance.
     */
    public function __construct(Collection $shifts)
    {
        $this->shifts = $shifts;
    }


    public function broadcastAs(): string
    {
        return 'multi-shifts-created';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('shift-plan.multi-shifts'),
        ];
    }

    public function broadcastWith(): array
    {
        $shifts = Shift::query()
            ->whereIn('id', $this->shifts->pluck('id')->all())
            ->with([
                'shiftsQualifications',
                'globalQualifications',
                'users.globalQualifications',
                'freelancer.globalQualifications',
                'serviceProvider.globalQualifications',
                'craft.craftShiftPlaner:id,first_name,last_name,position,business,profile_photo_path',
                'project.status:id,name,color',
                'project.users:id',
                'project.groups:id,name,state,artists,is_group,icon,color',
                'project.groups.status:id,name,color',
                'project.groups.users:id',
                'shiftGroup:id,name',
            ])
            ->get();

        // Newly created shifts may reference a project/craft/group not yet present in the
        // client's lookup maps; ship the lookup entries so the broadcast renders the project
        // link immediately instead of only after a full reload.
        $projectsById = [];
        $craftsById = [];
        $shiftGroupsById = [];

        foreach ($shifts as $shift) {
            if ($shift->project && !isset($projectsById[$shift->project->id])) {
                $projectsById[$shift->project->id] = ShiftCalendarService::buildProjectLookupEntry($shift->project);
            }
            if ($shift->craft && !isset($craftsById[$shift->craft->id])) {
                $craftsById[$shift->craft->id] = ShiftCalendarService::buildCraftLookupEntry($shift->craft);
            }
            if ($shift->shiftGroup && !isset($shiftGroupsById[$shift->shiftGroup->id])) {
                $shiftGroupsById[$shift->shiftGroup->id] = [
                    'id' => $shift->shiftGroup->id,
                    'name' => $shift->shiftGroup->name,
                ];
            }
        }

        return [
            'shifts' => $shifts->map(fn (Shift $shift) => ShiftDTO::fromModel($shift, $shift->project)),
            'lookups' => [
                'projectsById' => $projectsById,
                'craftsById' => $craftsById,
                'shiftGroupsById' => $shiftGroupsById,
            ],
        ];
    }
}
