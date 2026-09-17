<?php

namespace Artwork\Modules\ExternalIssue\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalIssue\Http\Requests\StoreExternalIssueFileRequest;
use Artwork\Modules\ExternalIssue\Http\Requests\UpdateExternalIssueFileRequest;
use Artwork\Modules\ExternalIssue\Models\ExternalIssueFile;

class ExternalIssueFileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExternalIssueFileRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ExternalIssueFile $externalIssueFile): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExternalIssueFile $externalIssueFile): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExternalIssueFileRequest $request, ExternalIssueFile $externalIssueFile): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExternalIssueFile $externalIssueFile): void
    {
        //
    }
}
