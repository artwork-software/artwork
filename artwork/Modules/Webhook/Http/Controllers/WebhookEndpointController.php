<?php

namespace Artwork\Modules\Webhook\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Webhook\Enums\WebhookDeliveryStatus;
use Artwork\Modules\Webhook\Http\Requests\StoreWebhookEndpointRequest;
use Artwork\Modules\Webhook\Http\Requests\UpdateWebhookEndpointRequest;
use Artwork\Modules\Webhook\Jobs\SendWebhookJob;
use Artwork\Modules\Webhook\Models\WebhookDelivery;
use Artwork\Modules\Webhook\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class WebhookEndpointController extends Controller
{
    public function store(StoreWebhookEndpointRequest $request): RedirectResponse
    {
        $this->authorize('create', WebhookEndpoint::class);

        // Das Geheimnis wird serverseitig erzeugt, nie vom Anwender gesetzt, und ist wie ein
        // API-Schlüssel nur einmalig sichtbar.
        $secret = Str::random(64);

        WebhookEndpoint::create([
            'name' => $request->string('name')->toString(),
            'url' => $request->string('url')->toString(),
            'secret' => $secret,
            'subscribed_events' => $request->validated('subscribed_events'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()
            ->with('success', __('Webhook endpoint created.'))
            ->with('webhookSecret', $secret);
    }

    public function update(
        UpdateWebhookEndpointRequest $request,
        WebhookEndpoint $webhookEndpoint
    ): RedirectResponse {
        $this->authorize('update', WebhookEndpoint::class);

        $webhookEndpoint->update([
            'name' => $request->string('name')->toString(),
            'url' => $request->string('url')->toString(),
            'subscribed_events' => $request->validated('subscribed_events'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', __('Webhook endpoint updated.'));
    }

    public function destroy(WebhookEndpoint $webhookEndpoint): RedirectResponse
    {
        $this->authorize('delete', WebhookEndpoint::class);

        $webhookEndpoint->delete();

        return back()->with('success', __('Webhook endpoint deleted.'));
    }

    public function deliveries(WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $this->authorize('view', WebhookEndpoint::class);

        return response()->json([
            'deliveries' => $webhookEndpoint->deliveries()
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }

    /**
     * Stellt eine bereits protokollierte Zustellung erneut zu — etwa nachdem der Empfänger
     * repariert wurde und die Versuche verbraucht waren.
     */
    public function redeliver(WebhookDelivery $webhookDelivery): RedirectResponse
    {
        $this->authorize('update', WebhookEndpoint::class);

        // Nur erschöpfte Zustellungen dürfen neu angestoßen werden: Bei pending/failed läuft die
        // Retry-Kette des ursprünglichen Jobs noch — ein zweiter Job würde denselben Empfänger
        // doppelt beliefern und sich mit dem ersten die attempt-/status-Felder überschreiben.
        if ($webhookDelivery->status !== WebhookDeliveryStatus::EXHAUSTED) {
            return back()->with('error', __('Only exhausted deliveries can be sent again.'));
        }

        $webhookDelivery->update([
            'status' => WebhookDeliveryStatus::PENDING,
            'attempt' => 0,
            'error' => null,
            'response_status' => null,
            'next_retry_at' => null,
        ]);

        SendWebhookJob::dispatch($webhookDelivery->getKey());

        return back()->with('success', __('Delivery queued again.'));
    }
}
