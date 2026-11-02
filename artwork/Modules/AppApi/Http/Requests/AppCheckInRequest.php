<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A scanned QR payload or a ticket picked from the guest list. The scan id is minted on the device
 * per attempt, so a retry after a timeout replays the first answer instead of admitting twice.
 */
class AppCheckInRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'scan_id' => ['required', 'uuid'],
            'payload' => ['required_without:ticket_id', 'prohibits:ticket_id', 'string', 'max:512'],
            'ticket_id' => ['required_without:payload', 'uuid'],
        ];
    }

    /** @return array{ticketId: string}|array{payload: string} */
    public function attempt(): array
    {
        return $this->filled('payload')
            ? ['payload' => (string) $this->validated('payload')]
            : ['ticketId' => (string) $this->validated('ticket_id')];
    }
}
