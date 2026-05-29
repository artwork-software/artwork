<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalAccess\Http\Requests\AddRecipientRequest;
use Artwork\Modules\ExternalAccess\Http\Requests\UpdateExternalAccessSettingsRequest;
use Artwork\Modules\ExternalAccess\Http\Requests\UpdateRecipientRequest;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessNotificationRecipient;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessNotificationRecipientService;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExternalAccessSettingsController extends Controller
{
    public function __construct(
        private readonly ExternalAccessSettings $settings,
        private readonly ExternalAccessSettingsResolver $resolver,
        private readonly ExternalAccessNotificationRecipientService $recipientService,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Settings/ExternalAccess/Index', [
            'settings' => [
                'company_name_override' => $this->settings->company_name_override,
                'effective_company_name' => $this->resolver->companyName(),
                'effective_company_name_without_override' => $this->resolver->companyNameFallback(),
                'default_crm_access_months' => $this->settings->default_crm_access_months,
                'default_tab_access_days' => $this->settings->default_tab_access_days,
                'login_token_lifetime_minutes' => $this->settings->login_token_lifetime_minutes,
                'session_idle_timeout_minutes' => $this->settings->session_idle_timeout_minutes,
                'session_absolute_lifetime_minutes' => $this->settings->session_absolute_lifetime_minutes,
                'rate_limit_request_link_per_email_per_hour' =>
                    $this->settings->rate_limit_request_link_per_email_per_hour,
                'rate_limit_request_link_per_ip_per_hour' =>
                    $this->settings->rate_limit_request_link_per_ip_per_hour,
            ],
            'notificationRecipients' => $this->recipientService->indexForUi(),
            'availableNotificationTypes' => $this->availableNotificationTypes(),
            'recipientOptions' => $this->recipientService->resolveOptionsForUi(),
        ]);
    }

    public function update(UpdateExternalAccessSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->company_name_override = $validated['company_name_override'];
        $this->settings->default_crm_access_months = $validated['default_crm_access_months'];
        $this->settings->default_tab_access_days = $validated['default_tab_access_days'];
        $this->settings->login_token_lifetime_minutes = $validated['login_token_lifetime_minutes'];
        $this->settings->session_idle_timeout_minutes = $validated['session_idle_timeout_minutes'];
        $this->settings->session_absolute_lifetime_minutes = $validated['session_absolute_lifetime_minutes'];
        $this->settings->rate_limit_request_link_per_email_per_hour =
            $validated['rate_limit_request_link_per_email_per_hour'];
        $this->settings->rate_limit_request_link_per_ip_per_hour =
            $validated['rate_limit_request_link_per_ip_per_hour'];
        $this->settings->save();

        activity('external_access_settings')
            ->causedBy($request->user())
            ->withProperties($validated)
            ->log('settings_updated');

        return redirect()->back()->with('status', __('Settings have been updated.'));
    }

    public function addRecipient(AddRecipientRequest $request): RedirectResponse
    {
        $this->recipientService->addRecipient(
            type: $request->validated('recipient_type'),
            id: (int) $request->validated('recipient_id'),
            notificationTypes: $request->validated('notification_types'),
            createdBy: $request->user(),
        );

        return redirect()->back()->with('status', __('Recipient added.'));
    }

    public function updateRecipient(
        ExternalAccessNotificationRecipient $recipient,
        UpdateRecipientRequest $request,
    ): RedirectResponse {
        $this->recipientService->updateRecipient($recipient, $request->validated('notification_types'));

        return redirect()->back()->with('status', __('Recipient updated.'));
    }

    public function removeRecipient(ExternalAccessNotificationRecipient $recipient): RedirectResponse
    {
        $this->recipientService->removeRecipient($recipient);

        return redirect()->back()->with('status', __('Recipient removed.'));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function availableNotificationTypes(): array
    {
        return [
            [
                'value' => NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED->value,
                'label' => __('External CRM submission'),
            ],
            [
                'value' => NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED->value,
                'label' => __('External tab component update'),
            ],
        ];
    }
}
