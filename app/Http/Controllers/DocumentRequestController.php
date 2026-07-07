<?php

namespace App\Http\Controllers;

use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Crm\Services\CrmContactService;
use Artwork\Modules\Crm\Services\CrmContactTypeService;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\DocumentRequest\Http\Resources\DocumentRequestResource;
use Artwork\Modules\DocumentRequest\Models\DocumentRequest;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Events\UpdateProjectContractsDocuments;
use Artwork\Modules\Project\Services\ProjectTabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;

class DocumentRequestController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ProjectTabService $projectTabService,
        private readonly CrmContactTypeService $crmContactTypeService,
        private readonly CrmContactService $crmContactService,
        private readonly CrmPropertyGroupService $crmPropertyGroupService,
    ) {
    }

    /**
     * Display a listing of the document requests.
     */
    public function index(): Response|ResponseFactory
    {
        $userId = Auth::id();

        $eagerLoad = ['requester', 'requested', 'project', 'contract', 'contractType', 'companyType', 'crmContact.contactType'];

        // Get requests created by the user
        $createdRequests = DocumentRequest::where('requester_id', $userId)
            ->with($eagerLoad)
            ->get();

        // Get requests assigned to the user
        $assignedRequests = DocumentRequest::where('requested_id', $userId)
            ->with($eagerLoad)
            ->get();

        // Get requests that are not assigned to any user
        $unassignedRequests = DocumentRequest::whereNull('requested_id')
            ->with($eagerLoad)
            ->get();

        return inertia('DocumentRequests/Index', [
            'createdRequests' => DocumentRequestResource::collection($createdRequests)->resolve(),
            'assignedRequests' => DocumentRequestResource::collection($assignedRequests)->resolve(),
            'unassignedRequests' => DocumentRequestResource::collection($unassignedRequests)->resolve(),
            'contract_types' => ContractType::all(),
            'company_types' => CompanyType::all(),
            'currencies' => Currency::all(),
            'first_project_calendar_tab_id' => $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                ProjectTabComponentEnum::CALENDAR
            ),
            'crmContactTypes' => $this->crmContactTypeService->getActive(),
        ]);
    }

    /**
     * Store a newly created document request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requested_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'contract_partner' => 'nullable|string|max:255',
            'contract_value' => 'nullable|numeric',
            'ksk_liable' => 'boolean',
            'ksk_amount' => 'nullable|numeric',
            'ksk_reason' => 'nullable|string',
            'foreign_tax' => 'boolean',
            'foreign_tax_amount' => 'nullable|numeric',
            'foreign_tax_city' => 'nullable|string|max:255',
            'foreign_tax_country' => 'nullable|string|max:255',
            'foreign_tax_reason' => 'nullable|string',
            'reverse_charge_amount' => 'nullable|numeric',
            'deadline_date' => 'nullable|date',
            'contract_type_id' => 'nullable|exists:contract_types,id',
            'company_type_id' => 'nullable|exists:company_types,id',
            'comment' => 'nullable|string',
            'contract_state' => 'nullable|string|max:255',
            'contract_state_comment' => 'nullable|string',
            'crm_contact_id' => 'nullable|exists:crm_contacts,id',
        ]);

        $documentRequest = DocumentRequest::create([
            'requester_id' => Auth::id(),
            'requested_id' => $validated['requested_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'status' => DocumentRequest::STATUS_OPEN,
            'contract_partner' => $validated['contract_partner'] ?? null,
            'contract_value' => $validated['contract_value'] ?? null,
            'ksk_liable' => $validated['ksk_liable'] ?? false,
            'ksk_amount' => $validated['ksk_amount'] ?? null,
            'ksk_reason' => $validated['ksk_reason'] ?? null,
            'foreign_tax' => $validated['foreign_tax'] ?? false,
            'foreign_tax_amount' => $validated['foreign_tax_amount'] ?? null,
            'foreign_tax_city' => $validated['foreign_tax_city'] ?? null,
            'foreign_tax_country' => $validated['foreign_tax_country'] ?? null,
            'foreign_tax_reason' => $validated['foreign_tax_reason'] ?? null,
            'reverse_charge_amount' => $validated['reverse_charge_amount'] ?? null,
            'deadline_date' => $validated['deadline_date'] ?? null,
            'contract_type_id' => $validated['contract_type_id'] ?? null,
            'company_type_id' => $validated['company_type_id'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'contract_state' => $validated['contract_state'] ?? null,
            'contract_state_comment' => $validated['contract_state_comment'] ?? null,
            'crm_contact_id' => $validated['crm_contact_id'] ?? null,
        ]);

        // Send notification to requested user (only if a user was assigned)
        if ($documentRequest->requested_id) {
            $this->sendDocumentRequestNotification($documentRequest);
        }

        return Redirect::back();
    }

    /**
     * Update the specified document request.
     */
    public function update(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $validated = $request->validate([
            'requested_id' => 'sometimes|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'status' => 'sometimes|in:open,in_progress,completed',
            'contract_partner' => 'nullable|string|max:255',
            'contract_value' => 'nullable|numeric',
            'ksk_liable' => 'boolean',
            'ksk_amount' => 'nullable|numeric',
            'ksk_reason' => 'nullable|string',
            'foreign_tax' => 'boolean',
            'foreign_tax_amount' => 'nullable|numeric',
            'foreign_tax_city' => 'nullable|string|max:255',
            'foreign_tax_country' => 'nullable|string|max:255',
            'foreign_tax_reason' => 'nullable|string',
            'reverse_charge_amount' => 'nullable|numeric',
            'deadline_date' => 'nullable|date',
            'contract_type_id' => 'nullable|exists:contract_types,id',
            'company_type_id' => 'nullable|exists:company_types,id',
            'comment' => 'nullable|string',
            'contract_state' => 'nullable|string|max:255',
            'contract_state_comment' => 'nullable|string',
            'contract_id' => 'nullable|exists:contracts,id',
            'crm_contact_id' => 'nullable|exists:crm_contacts,id',
        ]);

        $documentRequest->update($validated);

        // If contract was uploaded and status changed to completed, notify requester
        // Only send notification if a user was assigned
        if (isset($validated['contract_id']) && $validated['status'] === DocumentRequest::STATUS_COMPLETED && $documentRequest->requested_id) {
            $this->sendDocumentRequestCompletedNotification($documentRequest);
        }

        // Broadcast update for project contracts/documents component
        if ($documentRequest->project_id) {
            broadcast(new UpdateProjectContractsDocuments($documentRequest->project_id));
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

        // Notify requester that the document has been uploaded (only if a user was assigned)
        if ($documentRequest->requested_id) {
            $this->sendDocumentRequestCompletedNotification($documentRequest);
        }

        // Broadcast update for project contracts/documents component
        if ($documentRequest->project_id) {
            broadcast(new UpdateProjectContractsDocuments($documentRequest->project_id));
        }

        return Redirect::back();
    }

    /**
     * Get CRM contact data for a document request (permission-aware).
     */
    public function getCrmContactData(DocumentRequest $documentRequest): JsonResponse
    {
        if (!$documentRequest->crm_contact_id) {
            return response()->json(null);
        }

        $contact = $this->crmContactService->findById($documentRequest->crm_contact_id);

        if (!$contact) {
            return response()->json(null);
        }

        $contact->load(['contactType.properties', 'propertyValues.property.group']);

        $user = auth()->user();
        $deptIds = $user->departments?->pluck('id')->toArray() ?? [];
        $isCrmManager = $user->can(PermissionEnum::CRM_MANAGER->value);

        $groups = $this->crmPropertyGroupService->getVisibleForUser($user->id, $deptIds, $isCrmManager);

        return response()->json([
            'contact' => $contact,
            'propertyGroups' => $groups,
        ]);
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
            'id' => Str::uuid()->toString(),
            'type' => 'info',
            'message' => $notificationTitle
        ];

        $notificationDescription = [
            1 => [
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
            [
                'user' => $requestedUser->first_name . ' ' . $requestedUser->last_name
            ],
            $requesterUser->language
        );

        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
            'type' => 'success',
            'message' => $notificationTitle
        ];

        $notificationDescription = [
            1 => [
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
