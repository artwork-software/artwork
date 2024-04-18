<?php

namespace Artwork\Core\Database\Models;

// phpcs:ignoreFile
// Exists to let models from third party interact with services/repositories
interface CanSubstituteBaseModel
{
    public function delete();
    public function forceDelete();
    public function deleteOrFail();
    public function update();
    public function updateOrFail();
    public function saveOrFail();
    public function save();
    public function restore();
    public function restoreQuietly();
}
