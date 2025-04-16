<?php

namespace Artwork\Modules\Contacts\Models\Traits;


use Artwork\Modules\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasContacts
{
    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }
    public function getPrimaryContact(): \Illuminate\Database\Eloquent\Model
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    public function unsetAllPrimaryForModel($model): void
    {
        $model->contacts()->update(['is_primary' => false]);
    }
}