<?php

namespace App\Http\Resources;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shift
 */
class MinimalShiftPlanShiftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->getAttribute('id'),
            'start' => $this->getAttribute('start'),
            'end' => $this->getAttribute('end'),
            'craft' => $this->getAttribute('craft')?->only(['id', 'name', 'abbreviation']),
            'users' => $this->getAttribute('users')->map(
                function (User $user): array {
                    return [
                        'id' => $user->getAttribute('id'),
                        'pivot' => $user->getRelation('pivot')->only('shift_qualification_id'),
                        'globalQualifications' => $user->getAttribute('globalQualifications')?->pluck('id')->toArray() ?? []
                    ];
                }
            ),
            'freelancer' => $this->getAttribute('freelancer')->map(
                function (Freelancer $freelancer): array {
                    return [
                        'id' => $freelancer->getAttribute('id'),
                        'pivot' => $freelancer->getRelation('pivot')->only('shift_qualification_id'),
                        'globalQualifications' => $freelancer->getAttribute('globalQualifications')?->pluck('id')->toArray() ?? []
                    ];
                }
            ),
            'service_provider' => $this->getAttribute('serviceProvider')->map(
                function (ServiceProvider $serviceProvider): array {
                    return [
                        'id' => $serviceProvider->getAttribute('id'),
                        'pivot' => $serviceProvider->getRelation('pivot')->only('shift_qualification_id'),
                        'globalQualifications' => $serviceProvider->getAttribute('globalQualifications')?->pluck('id')->toArray() ?? []
                    ];
                }
            ),
            'break_minutes' => $this->getAttribute('break_minutes'),
            'description' => $this->getAttribute('description'),
            'formatted_dates' => $this->getAttribute('formatted_dates'),
            'shifts_qualifications' => $this->getAttribute('shiftsQualifications')->map(
                function (ShiftsQualifications $shiftsQualifications): array {
                    return [
                        "id" => $shiftsQualifications->getAttribute('id'),
                        "shift_id" => $shiftsQualifications->getAttribute('shift_id'),
                        "shift_qualification_id" => $shiftsQualifications->getAttribute('shift_qualification_id'),
                        "value" => $shiftsQualifications->getAttribute('value'),
                    ];
                }
            ),
            'worker_count' => $this->getAttribute('users')->count() +
                $this->getAttribute('freelancer')->count() +
                $this->getAttribute('serviceProvider')->count(),
            'max_worker_count' => $this->getAttribute('shiftsQualifications')->sum('value')
        ];
    }
}
