<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'has_restricted_permissions',
        'permission_mode',
        'inventory_tag_group_id',
        'position',
    ];

    protected $casts = [
        'has_restricted_permissions' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(InventoryTagGroup::class, 'inventory_tag_group_id', 'id');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryArticle::class,
            'inventory_article_inventory_tag',
            'inventory_tag_id',
            'inventory_article_id'
        );
    }

    public function allowedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'inventory_tag_user',
            'inventory_tag_id',
            'user_id'
        );
    }

    public function allowedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(
            Department::class,
            'inventory_tag_department',
            'inventory_tag_id',
            'department_id'
        );
    }
}
