<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Database\Seeders\Demo\Support\DemoExtrasPools;
use Database\Seeders\Demo\Support\DemoProjectPools;
use Database\Seeders\Demo\Support\DemoRandom;
use Illuminate\Database\Seeder;

/**
 * Künstler*innen, Unterkünfte und Aufenthalte: Anreise vor dem ersten
 * Termin, Abreise nach dem letzten — an Eigenproduktionen und Gastspielen.
 */
class DemoArtistResidencySeeder extends Seeder
{
    public ?string $from = null;
    public int $months = 6;

    public function run(): void
    {
        $random = new DemoRandom('artist-residencies');

        $artists = [];
        foreach (DemoExtrasPools::ARTISTS as $definition) {
            $artists[] = Artist::firstOrCreate(
                ['first_name' => $definition['first_name'], 'last_name' => $definition['last_name']],
                [
                    'name' => $definition['first_name'] . ' ' . $definition['last_name'],
                    'position' => $definition['position'],
                ]
            );
        }

        $accommodations = [];
        foreach (DemoExtrasPools::ACCOMMODATIONS as $definition) {
            $costPerNight = $definition['cost_per_night'];
            unset($definition['cost_per_night']);
            $accommodation = Accommodation::firstOrCreate(['name' => $definition['name']], $definition);
            $accommodation->costPerNight = $costPerNight; // nur zur Weitergabe an die Aufenthalte
            $accommodations[] = $accommodation;
        }

        $windowStart = ($this->from !== null
            ? Carbon::createFromFormat('Y-m', $this->from)
            : Carbon::now()->subMonths(2))->startOfMonth();
        $windowEnd = $windowStart->copy()->addMonths($this->months)->endOfDay();

        $projects = Project::query()
            ->get()
            ->filter(static fn (Project $project) => in_array(
                DemoProjectPools::archetypeForProjectName($project->name),
                ['eigenproduktion', 'gastspiel'],
                true
            ));

        $created = 0;
        foreach ($projects as $project) {
            if (ArtistResidency::query()->where('project_id', $project->id)->exists()) {
                continue;
            }
            $firstEvent = $project->events()->min('start_time');
            $lastEvent = $project->events()->max('end_time');
            if ($firstEvent === null || Carbon::parse($firstEvent)->gt($windowEnd) || Carbon::parse($lastEvent)->lt($windowStart)) {
                continue;
            }

            $rng = $random->fork($project->name);
            $arrival = Carbon::parse($firstEvent)->subDay();
            $departure = Carbon::parse($lastEvent)->addDay();
            $days = max(1, (int) $arrival->diffInDays($departure));

            foreach ($rng->pickMany($artists, $rng->int(2, 4)) as $artist) {
                $accommodation = $rng->pick($accommodations);
                ArtistResidency::create([
                    'artist_id' => $artist->id,
                    'accommodation_id' => $accommodation->id,
                    'project_id' => $project->id,
                    'arrival_date' => $arrival->toDateString(),
                    'arrival_time' => '15:00',
                    'departure_date' => $departure->toDateString(),
                    'departure_time' => '11:00',
                    'days' => $days,
                    'type_of_room' => $rng->pick(DemoExtrasPools::ROOM_TYPES),
                    'cost_per_night' => $accommodation->costPerNight,
                    'daily_allowance' => 28.0,
                    'additional_daily_allowance' => 0,
                    'breakfast_count' => $days,
                    'breakfast_deduction_per_day' => 5.6,
                    'description' => 'Aufenthalt für ' . $project->name,
                    'do_not_save_artist' => false,
                    'name' => $artist->name,
                    'first_name' => $artist->first_name,
                    'last_name' => $artist->last_name,
                    'position' => $artist->position,
                ]);
                $created++;
            }
        }

        $this->command?->info(sprintf(
            'Künstler*innenaufenthalte: %d Aufenthalte (%d Künstler*innen, %d Unterkünfte).',
            $created,
            count($artists),
            count($accommodations)
        ));
    }
}
