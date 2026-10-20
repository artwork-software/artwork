<?php

namespace Artwork\Modules\Room\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomUserRequest extends FormRequest
{
    /**
     * Raumzugriff: {users: [...], departments: [...]} – Teams geben allen Mitgliedern dieselben Rechte.
     * Die frühere Form (nur eine Liste von Personen) wird weiter angenommen.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        if (!$this->usesTeamPayload()) {
            return [
                '*.id' => 'required|exists:users,id',
                '*.is_admin' => 'required|boolean',
                '*.can_request' => 'required|boolean',
            ];
        }

        return [
            'users' => 'present|array',
            'users.*.id' => 'required|distinct|exists:users,id',
            'users.*.is_admin' => 'required|boolean',
            'users.*.can_request' => 'required|boolean',
            'departments' => 'present|array',
            'departments.*.id' => 'required|distinct|exists:departments,id',
            'departments.*.is_admin' => 'required|boolean',
            'departments.*.can_request' => 'required|boolean',
        ];
    }

    public function usesTeamPayload(): bool
    {
        return $this->has('users') || $this->has('departments');
    }

    /**
     * @return array<int, array{id: int, is_admin: bool, can_request: bool}>
     */
    public function roomUsers(): array
    {
        return $this->entries($this->usesTeamPayload() ? (array) $this->input('users', []) : $this->all());
    }

    /**
     * @return array<int, array{id: int, is_admin: bool, can_request: bool}>
     */
    public function roomDepartments(): array
    {
        return $this->usesTeamPayload() ? $this->entries((array) $this->input('departments', [])) : [];
    }

    /**
     * @param array<int|string, mixed> $entries
     * @return array<int, array{id: int, is_admin: bool, can_request: bool}>
     */
    private function entries(array $entries): array
    {
        return array_values(array_map(static fn (array $entry): array => [
            'id' => (int) $entry['id'],
            'is_admin' => filter_var($entry['is_admin'], FILTER_VALIDATE_BOOLEAN),
            'can_request' => filter_var($entry['can_request'], FILTER_VALIDATE_BOOLEAN),
        ], $entries));
    }
}
