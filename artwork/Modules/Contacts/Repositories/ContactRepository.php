<?php

namespace Artwork\Modules\Contacts\Repositories;

use Artwork\Modules\Contacts\Models\Contact;

class ContactRepository
{
    public function create(array $data): Contact
    {
        return Contact::create($data);
    }

    public function update(Contact $contact, array $data): bool
    {
        return $contact->update($data);
    }

    public function delete(Contact $contact): bool
    {
        return $contact->delete();
    }

    public function find(int $id): ?Contact
    {
        return Contact::find($id);
    }

    public function findByModel($model, int $id): ?Contact
    {
        return $model->contacts()->find($id);
    }
}
