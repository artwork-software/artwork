<?php

namespace App\Http\Controllers;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\DocumentRequest\Http\Resources\DocumentRequestResource;
use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class DocumentRequestController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ProjectTabService $projectTabService
    ) {
    }

    /**
     * Display a listing of the document requests.
     */
    public function index(): Response|ResponseFactory
    {
        $userId = Auth::id();

        // Get requests created by the user
        $createdRequests = DocumentRequest::where('requester_id', $userId)
            ->with(['requester', 'requested', 'project', 'contract', 'contractType', 'companyType'])
            ->get();

        // Get requests assigned to the user
        $assignedRequests = DocumentRequest::where('requested_id', $userId)
            ->with(['requester', 'requested', 'project', 'contract', 'contractType', 'companyType'])
            ->get();

        return inertia('DocumentRequests/Index', [
            'createdRequests' => DocumentRequestResource::collection($createdRequests)->resolve(),
            'assignedRequests' => DocumentRequestResource::collection($assignedRequests)->resolve(),
            'contract_types' => ContractType::all(),
            'company_types' => CompanyType::all(),
            'currencies' => Currency::all(),
        ]);
    }

    /**
     * Store a newly created document request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requested_id' => 'required|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contract_partner' => 'nullable|string|max:255',
            'contract_value' => 'nullable|numeric',
            'ksk_liable' => 'boolean',
            'ksk_amount' => 'nullable|numeric',
            'ksk_reason' => 'nullable|string',
            'foreign_tax' => 'boolean',
            'foreign_tax_amount' => 'nullable|numeric',
            'foreign_tax_reason' => 'nullable|string',
            'reverse_charge_amount' => 'nullable|numeric',
            'deadline_date' => 'nullable|date',
            'contract_type_id' => 'nullable|exists:contract_types,id',
            'company_type_id' => 'nullable|exists:company_types,id',
            'comment' => 'nullable|string',
        ]);

        $documentRequest = DocumentRequest::create([
            'requester_id' => Auth::id(),
            'requested_id' => $validated['requested_id'],
            'project_id' => $validated['project_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => DocumentRequest::STATUS_OPEN,
            'contract_partner' => $validated['contract_partner'] ?? null,
            'contract_value' => $validated['contract_value'] ?? null,
            'ksk_liable' => $validated['ksk_liable'] ?? false,
            'ksk_amount' => $validated['ksk_amount'] ?? null,
            'ksk_reason' => $validated['ksk_reason'] ?? null,
            'foreign_tax' => $validated['foreign_tax'] ?? false,
            'foreign_tax_amount' => $validated['foreign_tax_amount'] ?? null,
            'foreign_tax_reason' => $validated['foreign_tax_reason'] ?? null,
            'reverse_charge_amount' => $validated['reverse_charge_amount'] ?? null,
            'deadline_date' => $validated['deadline_date'] ?? null,
            'contract_type_id' => $validated['contract_type_id'] ?? null,
            'company_type_id' => $validated['company_type_id'] ?? null,
            'comment' => $validated['comment'] ?? null,
        ]);

        // Send notification to requested user
        $this->sendDocumentRequestNotification($documentRequest);

        return Redirect::route('document-requests.index');
    }

    /**
     * Update the specified document request.
     */
    public function update(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $validated = $request->validate([
            'requested_id' => 'sometimes|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:open,in_progress,completed',
            'contract_partner' => 'nullable|string|max:255',
            'contract_value' => 'nullable|numeric',
            'ksk_liable' => 'boolean',
            'ksk_amount' => 'nullable|numeric',
            'ksk_reason' => 'nullable|string',
            'foreign_tax' => 'boolean',
            'foreign_tax_amount' => 'nullable|numeric',
            'foreign_tax_reason' => 'nullable|string',
            'reverse_charge_amount' => 'nullable|numeric',
            'deadline_date' => 'nullable|date',
            'contract_type_id' => 'nullable|exists:contract_types,id',
            'company_type_id' => 'nullable|exists:company_types,id',
            'comment' => 'nullable|string',
            'contract_id' => 'nullable|exists:contracts,id',
        ]);

        $documentRequest->update($validated);

        // If contract was uploaded and status changed to completed, notify requester
        if (isset($validated['contract_id']) && $validated['status'] === DocumentRequest::STATUS_COMPLETED) {
            $this->sendDocumentRequestCompletedNotification($documentRequest);
        }

        return Redirect::back();
    }

    /**
     * Remove the specified document request.
     */
    public function destroy(DocumentRequest $documentRequest): RedirectResponse
    {
        $documentRequest->delete();

        return Redirect::route('document-requests.index');
    }

    /**
     * Link a contract to a document request (fulfill the request).
     */
    public function linkContract(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
        ]);

        $documentRequest->update([
            'contract_id' => $validated['contract_id'],
            'status' => DocumentRequest::STATUS_COMPLETED,
        ]);

        // Notify requester that the document has been uploaded
        $this->sendDocumentRequestCompletedNotification($documentRequest);

        return Redirect::back();
    }

    /**
     * Send notification when a document request is created.
     */
    private function sendDocumentRequestNotification(DocumentRequest $documentRequest): void
    {
        $requestedUser = $documentRequest->requested;
        $requesterUser = $documentRequest->requester;

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(2);

        if ($documentRequest->project_id) {
            $this->notificationService->setProjectId($documentRequest->project_id);
        }

        $this->notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_DOCUMENT_REQUEST_CREATED
        );

        $notificationTitle = __(
            'notification.document_request.created',
            ['name' => $requesterUser->first_name . ' ' . $requesterUser->last_name],
            $requestedUser->language
        );

        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'info',
            'message' => $notificationTitle
        ];

        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => $documentRequest->title,
                'href' => null
            ],
            2 => [
                'type' => 'link',
                'title' => __('View document requests'),
                'href' => route('document-requests.index'),
            ]
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($requestedUser);
        $this->notificationService->createNotification();
    }

    /**
     * Send notification when a document request is completed.
     */
    private function sendDocumentRequestCompletedNotification(DocumentRequest $documentRequest): void
    {
        $requesterUser = $documentRequest->requester;
        $requestedUser = $documentRequest->requested;

        $this->notificationService->setIcon('green');
        $this->notificationService->setPriority(2);

        if ($documentRequest->project_id) {
            $this->notificationService->setProjectId($documentRequest->project_id);
        }

        $this->notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_DOCUMENT_REQUEST_COMPLETED
        );

        $notificationTitle = __(
            'notification.document_request.completed',
            ['name' => $requestedUser->first_name . ' ' . $requestedUser->last_name],
            $requesterUser->language
        );

        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];

        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => $documentRequest->title,
                'href' => null
            ],
            2 => [
                'type' => 'link',
                'title' => __('View document requests'),
                'href' => route('document-requests.index'),
            ]
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo($requesterUser);
        $this->notificationService->createNotification();
    }
}
