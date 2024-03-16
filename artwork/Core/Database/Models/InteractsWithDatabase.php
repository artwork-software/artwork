<?php

namespace Artwork\Core\Database\Models;

// Exists to let models from third party interact with services/repositories
interface InteractsWithDatabase
{
    public function delete();
    public function forceDelete();
    public function deleteOrFail();
    public function update();
    public function updateOrFail();
    public function saveOrFail();
    public function save();
}
