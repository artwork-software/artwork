<?php

namespace Artwork\Modules\Sage100\Clients;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Sage100Client implements SageClient
{
    public function __construct(
        private readonly string $domain,
        private readonly string $endpoint,
        private readonly string $user,
        private readonly string $password
    ) {
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
            $msg = 'SageAPI-Client requested without necessary parameters. Return empty results.';
            Log::info($msg);
            report(new \Exception($msg));
            return [];
        }

        try {
            return $client
                ->get($this->endpoint, $query)
                ->json('$resources');
        } catch (\Throwable $t) {
            Log::error('SageAPI-Call erroneous for reason: ' . $t->getMessage());
            report($t);
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
            report($t);
            return false;
        }
    }
}
