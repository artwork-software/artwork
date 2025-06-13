<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\PrintLayoutComponents;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPrintLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'columns_header',
        'columns_footer',
        'columns_body',
        'order',
        'is_active',
        'user_id',
        'permission',
        'notes'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'notes' => 'array'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }

    public function headerComponents(): HasMany
    {
        return $this->hasMany(
            PrintLayoutComponents::class,
            'project_print_layout_id',
            'id'
        )->where('type', 'header');
    }

    public function footerComponents(): HasMany
    {
        return $this->hasMany(
            PrintLayoutComponents::class,
            'project_print_layout_id',
            'id'
        )->where('type', 'footer');
    }

    public function bodyComponents(): HasMany
    {
        return $this->hasMany(
            PrintLayoutComponents::class,
            'project_print_layout_id',
            'id'
        )->where('type', 'body');
    }

    public function components(): HasMany
    {
        return $this->hasMany(
            PrintLayoutComponents::class,
            'project_print_layout_id',
            'id'
        );
    }

}
