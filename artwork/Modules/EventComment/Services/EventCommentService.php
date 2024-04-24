<?php

namespace Artwork\Modules\EventComment\Services;

use Artwork\Modules\EventComment\Models\EventComment;
use Artwork\Modules\EventComment\Repositories\EventCommentRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class EventCommentService
{
    public function __construct(private EventCommentRepository $eventCommentRepository)
    {
    }

    public function delete(EventComment $eventComment): bool
    {
        return $this->eventCommentRepository->delete($eventComment);
    }

    public function deleteEventComments(Collection|array $eventComments): void
    {
        /** @var EventComment $eventComment */
        foreach ($eventComments as $eventComment) {
            $this->delete($eventComment);
        }
    }

    public function restoreEventComments(Collection|array $eventComments): void
    {
        /** @var EventComment $eventComment */
        foreach ($eventComments as $eventComment) {
            $this->restore($eventComment);
        }
    }

    public function forceDelete(EventComment $eventComment): bool
    {
        return $this->eventCommentRepository->forceDelete($eventComment);
    }

    public function restore(EventComment $eventComment): bool
    {
        return $this->eventCommentRepository->restore($eventComment);
    }
}
