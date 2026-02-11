<?php

namespace Artwork\Modules\DocumentRequest\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $requester_id
 * @property int $requested_id
 * @property int|null $project_id
 * @property int|null $contract_id
 * @property string $status
 * @property string|null $contract_partner
 * @property float|null $contract_value
 * @property bool $ksk_liable
 * @property float|null $ksk_amount
 * @property string|null $ksk_reason
 * @property bool $foreign_tax
 * @property float|null $foreign_tax_amount
 * @property string|null $foreign_tax_city
 * @property string|null $foreign_tax_country
 * @property string|null $foreign_tax_reason
 * @property float|null $reverse_charge_amount
 * @property string|null $contract_state
 * @property string|null $contract_state_comment
 * @property string|null $deadline_date
 * @property int|null $contract_type_id
 * @property int|null $company_type_id
 * @property string|null $comment
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 */
class DocumentRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'requester_id',
        'requested_id',
        'project_id',
        'contract_id',
        'status',
        'contract_partner',
        'contract_value',
        'ksk_liable',
        'ksk_amount',
        'ksk_reason',
        'foreign_tax',
        'foreign_tax_amount',
        'foreign_tax_city',
        'foreign_tax_country',
        'foreign_tax_reason',
        'reverse_charge_amount',
        'deadline_date',
        'contract_type_id',
        'company_type_id',
        'comment',
        'contract_state',
        'contract_state_comment',
    ];

    protected $guarded = [
        'id'
    ];

    protected $casts = [
        'ksk_liable' => 'boolean',
        'ksk_amount' => 'decimal:2',
        'foreign_tax' => 'boolean',
        'foreign_tax_amount' => 'decimal:2',
        'reverse_charge_amount' => 'decimal:2',
        'contract_value' => 'decimal:2',
        'deadline_date' => 'date',
    ];

    /**
     * User who created the request
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id', 'id', 'users')
            ->without(['calender_settings', 'shifts', 'vacations', 'vacation_series', 'vacationer']);
    }

    /**
     * User who should fulfill the request
     */
    public function requested(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_id', 'id', 'users')
            ->without(['calender_settings', 'shifts', 'vacations', 'vacation_series', 'vacationer']);
    }

    /**
     * Associated project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }

    /**
     * Uploaded contract (when request is fulfilled)
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'id', 'contracts');
    }

    /**
     * Contract type
     */
    public function contractType(): BelongsTo
    {
        return $this->belongsTo(
            \Artwork\Modules\Contract\Models\ContractType::class,
            'contract_type_id',
            'id',
            'contract_types'
        );
    }

    /**
     * Company type (legal form)
     */
    public function companyType(): BelongsTo
    {
        return $this->belongsTo(
            \Artwork\Modules\CompanyType\Models\CompanyType::class,
            'company_type_id',
            'id',
            'company_types'
        );
    }

    /**
     * Check if the request is open
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if the request is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if the request is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
