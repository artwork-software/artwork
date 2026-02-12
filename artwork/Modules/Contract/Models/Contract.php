<?php

namespace Artwork\Modules\Contract\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property float|null $ksk_amount
 * @property string|null $ksk_reason
 * @property bool $resident_abroad
 * @property bool $foreign_tax
 * @property float|null $foreign_tax_amount
 * @property string|null $foreign_tax_city
 * @property string|null $foreign_tax_country
 * @property string|null $foreign_tax_reason
 * @property float|null $reverse_charge_amount
 * @property string|null $contract_state
 * @property string|null $contract_state_comment
 * @property string|null $deadline_date
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
        'ksk_amount',
        'ksk_reason',
        'resident_abroad',
        'foreign_tax',
        'foreign_tax_amount',
        'foreign_tax_city',
        'foreign_tax_country',
        'foreign_tax_reason',
        'contract_state',
        'contract_state_comment',
        'reverse_charge_amount',
        'deadline_date',
    ];

    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'ksk_liable' => 'boolean',
        'ksk_amount' => 'decimal:2',
        'resident_abroad' => 'boolean',
        'foreign_tax' => 'boolean',
        'foreign_tax_amount' => 'decimal:2',
        'reverse_charge_amount' => 'decimal:2',
        'deadline_date' => 'date',
        'is_freed' => 'boolean',
        'has_power_of_attorney' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(
            Project::class,
            'project_id',
            'id',
            'projects'
        );
    }

    //@todo: fix phpcs error - refactor function name to companyType
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function company_type(): BelongsTo
    {
        return $this->belongsTo(
            CompanyType::class,
            'company_type_id',
            'id',
            'company_types'
        );
    }

    //@todo: fix phpcs error - refactor function name to contractType
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function contract_type(): BelongsTo
    {
        return $this->belongsTo(
            ContractType::class,
            'contract_type_id',
            'id',
            'contract_types'
        );
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            Currency::class,
            'currency_id',
            'id',
            'currencies'
        );
    }

    public function accessingUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function accessingDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
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
        return $this->hasMany(Comment::class, 'contract_id', 'id');
    }
}
