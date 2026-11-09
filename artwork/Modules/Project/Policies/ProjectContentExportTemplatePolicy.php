<?php

namespace Artwork\Modules\Project\Policies;

use Artwork\Modules\Project\Models\ProjectContentExportTemplate;
use Artwork\Modules\User\Models\User;

/**
 * Vorlagen des Projektinhalte-Exports sehen alle mit Exportrecht (Routen-Middleware). Ändern und löschen darf
 * nur, wer die Vorlage angelegt hat; Admins über Gate::before.
 */
class ProjectContentExportTemplatePolicy
{
    public function update(User $user, ProjectContentExportTemplate $template): bool
    {
        return $template->created_by !== null && $template->created_by === $user->id;
    }

    public function delete(User $user, ProjectContentExportTemplate $template): bool
    {
        return $this->update($user, $template);
    }
}
