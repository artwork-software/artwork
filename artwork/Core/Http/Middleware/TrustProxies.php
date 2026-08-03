<?php

namespace Artwork\Core\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    protected $proxies = null;

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Ohne vertrauenswuerdige Proxies werden die X-Forwarded-*-Header ignoriert und
     * Laravel erzeugt hinter einem TLS-terminierenden Proxy http://-URLs. Die Liste
     * kommt aus der Config, damit sie pro Installation gesetzt werden kann.
     */
    public function __construct()
    {
        $proxies = config('app.trusted_proxies');

        $this->proxies = $proxies === '*'
            ? '*'
            : array_values(array_filter(array_map('trim', explode(',', (string) $proxies))));
    }
}
