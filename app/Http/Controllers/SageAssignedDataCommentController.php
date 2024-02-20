<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Http\Requests\StoreSageAssignedDataCommentRequest;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Artwork\Modules\Budget\Services\SageAssignedDataCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class SageAssignedDataCommentController extends Controller
{
    public function __construct(private readonly SageAssignedDataCommentService $sageAssignedDataCommentService)
    {
        $this->authorizeResource(SageAssignedDataComment::class, 'sageAssignedDataComment');
    }

    public function store(StoreSageAssignedDataCommentRequest $request): RedirectResponse
    {
        $sageAssignedDataComment = $this->sageAssignedDataCommentService->createFromRequest($request);

        return Redirect::back()->with('recentlyCreatedSageAssignedDataCommentId', $sageAssignedDataComment->id);
    }

    public function destroy(SageAssignedDataComment $sageAssignedDataComment): void
    {
        $this->sageAssignedDataCommentService->delete($sageAssignedDataComment);
    }
}
