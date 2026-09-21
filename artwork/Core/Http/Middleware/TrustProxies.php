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
     * kommt aus der Config (app.trusted_proxies, ENV TRUSTED_PROXIES): kommagetrennte
     * IPs/CIDRs, Default = private Netze + Loopback. '*' bleibt als expliziter Opt-in
     * moeglich, ist aber nie Default — sonst kann jeder Client X-Forwarded-For faelschen
     * und damit die IP-basierten Rate-Limits (Login, Magic-Link, API) aushebeln.
     */
    public function __construct()
    {
        $proxies = config('app.trusted_proxies');

        $this->proxies = $proxies === '*'
            ? '*'
            : array_values(array_filter(array_map('trim', explode(',', (string) $proxies))));
    }
}
