<?php

namespace Artwork\Modules\IndividualTimes\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;

class IndividualTimeSubjectsSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $limit = (int) $request->get('limit', 10);

        // User
        $userQuery = User::query()
            ->canWorkShifts()
            ->excludeDeletedPlaceholder()
            ->when($query !== '', function ($q) use ($query): void {
                $q->where(function ($qq) use ($query): void {
                    $qq->where('first_name', 'like', $query . '%')
                        ->orWhere('last_name', 'like', $query . '%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$query . '%']);
                });
            })
            ->limit($limit)
            ->get()
            ->map(function (User $user) {
                return [
                    'id'                => $user->id,
                    'type'              => 'user',
                    'display_name'      => $user->display_name,
                    'profile_photo_url' => $user->profile_photo_url,
                    'can_work_shifts'   => $user->can_work_shifts,
                ];
            });

        // Freelancer
        $freelancerQuery = Freelancer::query()
            ->canWorkShifts()
            ->when($query !== '', function ($q) use ($query): void {
                $q->where(function ($qq) use ($query): void {
                    $qq->where('first_name', 'like', $query . '%')
                        ->orWhere('last_name', 'like', $query . '%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$query . '%']);
                });
            })
            ->limit($limit)
            ->get()
            ->map(function (Freelancer $freelancer) {
                return [
                    'id'                => $freelancer->id,
                    'type'              => 'freelancer',
                    'display_name'      => $freelancer->display_name,
                    'profile_photo_url' => $freelancer->profile_photo_url,
                    'can_work_shifts'   => $freelancer->can_work_shifts,
                ];
            });

        // ServiceProvider
        $serviceProviderQuery = ServiceProvider::query()
            ->canWorkShifts()
            ->when($query !== '', function ($q) use ($query): void {
                $q->where('provider_name', 'like', $query . '%');
            })
            ->limit($limit)
            ->get()
            ->map(function (ServiceProvider $provider) {
                return [
                    'id'                => $provider->id,
                    'type'              => 'service_provider',
                    'display_name'      => $provider->name,
                    'profile_photo_url' => $provider->profile_photo_url,
                    'can_work_shifts'   => $provider->can_work_shifts,
                ];
            });

        // Am Ende:
        $results = collect($userQuery)      // -> Support\Collection statt Eloquent\Collection
        ->merge($freelancerQuery)
            ->merge($serviceProviderQuery)
            ->sortBy('display_name')
            ->values()
            ->all();

        return response()->json([
            'data' => $results,
        ]);
    }
}
