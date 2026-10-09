<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Core\Cache\ServiceWithArrayCache;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Cache\ProjectTabArrayCache;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Repositories\ProjectTabRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;

class ProjectTabService implements ServiceWithArrayCache
{
    public function __construct(
        private readonly ProjectTabRepository $projectTabRepository,
    ) {
    }


    private function authUser(): ?User
    {
        try {
            /** @var UserService $userService */
            $userService = app(UserService::class);
            return $userService->getAuthUser();
        } catch (\Throwable) {
            /** @var User|null $u */
            $u = auth()->user();
            return $u;
        }
    }

    public function findFirstProjectTab(): ProjectTab
    {
        $user = $this->authUser();
        $tab = $this->projectTabRepository->findFirstProjectTab($user);
        if (!$tab) {
            $tab = $this->projectTabRepository->findFirstProjectTab(null);
        }

        return $tab;
    }

    public function getDefaultOrFirstProjectTab(): ProjectTab
    {
        $user = $this->authUser();

        $tab = $this->projectTabRepository->getDefaultOrFirstProjectTab($user);

        if (!$tab) {
            $tab = $this->projectTabRepository->getDefaultOrFirstProjectTab(null);
        }

        return $tab;
    }

    public function getDefaultOrFirstProjectTabId(): int
    {
        return $this->getDefaultOrFirstProjectTab()->getAttribute('id');
    }

    public function getFirstProjectTabId(): int
    {
        return $this->findFirstProjectTab()->getAttribute('id');
    }

    public function findFirstProjectTabWithShiftsComponent(): ProjectTab|null
    {
        return $this->findFirstProjectTabWithType(ProjectTabComponentEnum::SHIFT_TAB);
    }

    public function findFirstProjectTabWithBusinessIntelligenceComponent(): ProjectTab|null
    {
        return $this->findFirstProjectTabWithType(ProjectTabComponentEnum::BUSINESS_INTELLIGENCE);
    }

    private function findFirstProjectTabWithType(ProjectTabComponentEnum $type): ProjectTab|null
    {
        $user = $this->authUser();
        $uid  = $user?->id ?? 0;

        $cacheKey = $type->name . '|u' . $uid;

        if (!$projectTab = ProjectTabArrayCache::getItemByName($cacheKey)) {
            $projectTab = $this->projectTabRepository
                ->findFirstProjectTabByComponentsComponentType($type, $user);

            if ($projectTab) {
                ProjectTabArrayCache::setItem($projectTab);
            }
        }

        return $projectTab;
    }

    public function getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum $type): int
    {
        return $this->findFirstProjectTabWithType($type)?->getAttribute('id') ??
            $this->getDefaultOrFirstProjectTabId();
    }

    public function findByIdWithoutCache(int $id): ?Model
    {
        return $this->projectTabRepository->findById($id);
    }

    public function findByNameWithoutCache(string $name): ?Model
    {
        if (!$name) {
            return null;
        }
        return $this->projectTabRepository->findByName($name);
    }
}
