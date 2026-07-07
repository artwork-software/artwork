<?php

namespace Artwork\Modules\User\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property boolean $ksk_liable
 * @property boolean $foreign_tax
 * @property string|null $date_from
 * @property string|null $date_to
 * @property array $legal_form_ids
 * @property array $contract_type_ids
 * @property string $created_at
 * @property string $updated_at
 */
class UserContractFilter extends Model
{
    protected $fillable = [
        'user_id',
        'ksk_liable',
        'foreign_tax',
        'date_from',
        'date_to',
        'legal_form_ids',
        'contract_type_ids',
    ];

    protected $casts = [
        'ksk_liable' => 'boolean',
        'foreign_tax' => 'boolean',
        'legal_form_ids' => 'array',
        'contract_type_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
