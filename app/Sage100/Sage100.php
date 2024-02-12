<?php

namespace App\Sage100;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Sage100
{
    public string $domain;

    protected PendingRequest $client;

    protected string $endpoint;

    protected string $user;

    protected string $password;

    public function __construct(
        string $domain,
        string $endpoint,
        string $user,
        string $password
    ) {
        $this->domain = $domain;
        $this->endpoint = $endpoint;
        $this->user = $user;
        $this->password = $password;
        $this->client = Http::baseUrl($domain)->acceptJson();
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->domain)
            ->withBasicAuth($this->user, $this->password)
            ->acceptJson()
            ->withOptions([
                'verify' => false
            ])
            ->throw();
    }

    /**
     * @return array<object>
     */

    public function getData(array $query = []): array
    {
        return $this->client()
            ->get($this->endpoint, $query)
            ->json('$resources');
    }
}
