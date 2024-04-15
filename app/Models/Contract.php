<?php

namespace App\Models;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $basename
 * @property string $contract_partner
 * @property int $amount
 * @property int $creator_id
 * @property int $project_id
 * @property string $description
 * @property string $contract_type_id
 * @property string $company_type_id
 * @property string $currency_id
 * @property bool $ksk_liable
 * @property bool $resident_abroad
 * @property bool $is_freed
 * @property bool $has_power_of_attorney
 * @property string $created_at
 * @property string $updated_at
 */
class Contract extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'basename',
        'contract_partner',
        'description',
        'is_freed',
        'has_power_of_attorney',
        'amount',
        'creator_id',
        'project_id',
        'contract_type_id',
        'company_type_id',
        'currency_id',
        'ksk_liable',
        'resident_abroad',
    ];

    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'ksk_liable' => 'boolean',
        'resident_abroad' => 'boolean',
        'is_freed' => 'boolean',
        'has_power_of_attorney' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    //@todo: fix phpcs error - refactor function name to companyType
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function company_type(): BelongsTo
    {
        return $this->belongsTo(CompanyType::class, 'company_type_id');
    }

    //@todo: fix phpcs error - refactor function name to contractType
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function contract_type(): BelongsTo
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function accessingUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id', 'id', 'users')
            ->without(['calender_settings', 'shifts', 'vacations', 'vacation_series', 'vacationer']);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(\Artwork\Modules\Project\Models\Comment::class, 'contract_id', 'id');
    }
}
