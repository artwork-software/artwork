<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProviderContacts extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_provider_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
    ];

    public function serviceProvider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }
}
