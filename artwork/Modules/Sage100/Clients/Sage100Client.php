<?php

namespace Artwork\Modules\Sage100\Clients;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Sage100Client
{
    public ?string $domain;

    private ?string $endpoint;

    private ?string $user;

    private ?string $password;

    public function __construct(
        ?string $domain,
        ?string $endpoint,
        ?string $user,
        ?string $password
    ) {
        $this->domain = $domain;
        $this->endpoint = $endpoint;
        $this->user = $user;
        $this->password = $password;
    }

    private function client(): PendingRequest|null
    {
        if (is_null($this->domain) || is_null($this->user) || is_null($this->password) || is_null($this->endpoint)) {
            return null;
        }

        return Http::baseUrl($this->domain)
            ->withBasicAuth($this->user, $this->password)
            ->withOptions([
                'verify' => false
            ])
            ->acceptJson()
            ->throw();
    }

    /**
     * @return array<object>
     */
    public function getData(array $query = []): array
    {
        $client = $this->client();

        if (!$client instanceof PendingRequest) {
            Log::info('SageAPI-Client requested without necessary parameters. Return empty results.');

            return [];
        }

        try {
            return $client
                ->get($this->endpoint, $query)
                ->json('$resources');
        } catch (\Throwable $t) {
            Log::error('SageAPI-Call erroneous for reason: ' . $t->getMessage());

            return [];
        }
    }

    public function testConnection(): bool
    {
        try {
            return $this->client()->get(
                $this->endpoint,
                [
                    "startIndex" => 0,
                    "count" => 1,
                ]
            )->status() === 200;
        } catch (\Throwable $t) {
            Log::error('SageAPI-Call connection test failed for reason: ' . $t->getMessage());

            return false;
        }
    }
}
