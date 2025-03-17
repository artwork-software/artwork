<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\Traits\HasInventoryProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryDetailedQuantityArticle extends Model
{
    use HasFactory;
    use HasInventoryProperties;
}
