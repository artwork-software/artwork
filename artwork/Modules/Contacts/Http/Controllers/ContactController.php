<?php

namespace Artwork\Modules\Contacts\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Contacts\Http\Requests\StoreContactRequest;
use Artwork\Modules\Contacts\Http\Requests\UpdateContactRequest;
use Artwork\Modules\Contacts\Models\Contact;
use Artwork\Modules\Contacts\Services\ContactService;

class ContactController extends Controller
{

    public function __construct(protected ContactService $contactService) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request, $model, $modelId)
    {
        $modelObject = $this->contactService->resolveModelInstance($model, $modelId);

        $this->contactService->createForModel($modelObject, $request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, Contact $contact)
    {
        $this->contactService->updateForModel($contact, $request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        $this->contactService->deleteFromModel($contact);
    }
}
