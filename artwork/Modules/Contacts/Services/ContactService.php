<?php

namespace Artwork\Modules\Contacts\Services;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Contacts\Models\Contact;
use Artwork\Modules\Contacts\Repositories\ContactRepository;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;

class ContactService
{
    protected ContactRepository $repository;

    public function __construct(ContactRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createForModel($model, array $data): Contact
    {
        return $model->contacts()->create($data);
    }

    public function updateForModel(Contact $contact, array $data): bool
    {

        if (!$contact) {
            throw new \RuntimeException('Kontakt nicht gefunden.');
        }

        return $this->repository->update($contact, $data);
    }

    public function deleteFromModel(Contact $contact): bool
    {
        if (! $contact) {
            throw new \RuntimeException('Kontakt nicht gefunden.');
        }

        return $this->repository->delete($contact);
    }

    public function resolveModelInstance(string $model, int $id): \Illuminate\Database\Eloquent\Model
    {
        $map = [
            'user' => User::class,
            'provider' => ServiceProvider::class,
            'accommodation' => Accommodation::class,
        ];

        if (!array_key_exists($model, $map)) {
            abort(404, 'Unbekanntes Modell: ' . $model);
        }

        return $map[$model]::findOrFail($id);
    }
}