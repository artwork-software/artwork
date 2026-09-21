<?php

namespace Artwork\Core\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Lässt nur URLs zu, deren Host auf öffentliche Adressen zeigt. Gedacht für Ziele, die der Server
 * selbst aufruft (Webhooks, OIDC-Discovery) — sonst ließe sich über eine Admin-Eingabe das interne
 * Netz abfragen (SSRF: Loopback, RFC1918, Link-Local/Cloud-Metadata 169.254.0.0/16, ULA, NAT64 …).
 * Sicherheits-Audit 21.09.2026, E NIEDRIG.
 *
 * Der Hostname wird aufgelöst (A + AAAA); jede zurückgelieferte Adresse muss öffentlich sein.
 * Nicht auflösbare Hosts werden abgelehnt (kein "unbekannt = erlaubt"). Für Tests lässt sich der
 * Resolver per resolveUsing() ersetzen; DNS-Rebinding nach der Validierung deckt die Regel nicht ab.
 */
class PublicUrlRule implements ValidationRule
{
    /** @var (callable(string): array<int, string>)|null */
    private static $resolver = null;

    /**
     * @param array<int, string> $allowedSchemes
     */
    public function __construct(private readonly array $allowedSchemes = ['http', 'https'])
    {
    }

    /**
     * Resolver ersetzen (Tests) — erhält den Hostnamen, liefert IP-Strings; null = System-DNS.
     *
     * @param (callable(string): array<int, string>)|null $resolver
     */
    public static function resolveUsing(?callable $resolver): void
    {
        self::$resolver = $resolver;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || trim($value) === '') {
            $fail(__('The :attribute must be a valid URL.'));
            return;
        }

        $parts = parse_url($value);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            $fail(__('The :attribute must be a valid URL.'));
            return;
        }

        if (!in_array(strtolower($parts['scheme']), $this->allowedSchemes, true)) {
            $fail(__('The :attribute must use one of these schemes: :schemes.', [
                'schemes' => implode(', ', $this->allowedSchemes),
            ]));
            return;
        }

        // Zugangsdaten in der URL (user:pass@host) sind ein klassischer Parser-Trick — nicht erlaubt.
        if (isset($parts['user']) || isset($parts['pass'])) {
            $fail(__('The :attribute must not contain credentials.'));
            return;
        }

        $host = rtrim(strtolower(trim($parts['host'], '[]')), '.');

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            $fail(__('The :attribute must point to a public host.'));
            return;
        }

        $ips = $this->resolve($host);
        if ($ips === []) {
            $fail(__('The :attribute host could not be resolved.'));
            return;
        }

        foreach ($ips as $ip) {
            if (!self::isPublicIp($ip)) {
                $fail(__('The :attribute must point to a public host.'));
                return;
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        // Numerische Schreibweisen wie 2130706433, 0x7f000001 oder 127.1 würde der System-Resolver
        // still zu 127.0.0.1 machen — sie sind keine gültige IP und kein Hostname.
        if (preg_match('/^[0-9a-fx.]+$/i', $host) === 1) {
            return ['0.0.0.0'];
        }

        if (self::$resolver !== null) {
            return array_values(array_unique(array_filter((self::$resolver)($host), 'is_string')));
        }

        // Auflösungsfehler sind hier ein Validierungsergebnis (leere Liste → Ablehnung), keine Warnung.
        $ips = @gethostbynamel($host) ?: []; // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        $records = @dns_get_record($host, DNS_AAAA) ?: []; // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        foreach ($records as $record) {
            if (!empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }

    public static function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return self::isPublicIpv4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return self::isPublicIpv6($ip);
        }

        return false;
    }

    private static function isPublicIpv4(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) {
            return false;
        }

        foreach (
            [
            '0.0.0.0/8',        // "this" network / unspecified
            '10.0.0.0/8',       // RFC1918
            '100.64.0.0/10',    // CGNAT (RFC6598)
            '127.0.0.0/8',      // loopback
            '169.254.0.0/16',   // link-local / cloud metadata
            '172.16.0.0/12',    // RFC1918
            '192.0.0.0/24',     // IETF protocol assignments
            '192.168.0.0/16',   // RFC1918
            '198.18.0.0/15',    // benchmarking
            '224.0.0.0/4',      // multicast
            '240.0.0.0/4',      // reserved + broadcast
            ] as $cidr
        ) {
            [$network, $bits] = explode('/', $cidr);
            $mask = -1 << (32 - (int) $bits);
            if ((ip2long($network) & $mask) === ($long & $mask)) {
                return false;
            }
        }

        return true;
    }

    private static function isPublicIpv6(string $ip): bool
    {
        $binary = inet_pton($ip);
        if ($binary === false || strlen($binary) !== 16) {
            return false;
        }

        // IPv4-mapped (::ffff:a.b.c.d) und NAT64 (64:ff9b::/96): eingebettete IPv4 prüfen
        $mapped = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff";
        $nat64 = "\x00\x64\xff\x9b\x00\x00\x00\x00\x00\x00\x00\x00";
        if (str_starts_with($binary, $mapped) || str_starts_with($binary, $nat64)) {
            $embedded = long2ip(unpack('N', substr($binary, 12))[1]);

            return $embedded !== false && self::isPublicIpv4($embedded);
        }

        foreach (
            [
            '::/128',       // unspecified
            '::1/128',      // loopback
            'fc00::/7',     // ULA
            'fe80::/10',    // link-local
            'fec0::/10',    // site-local (deprecated)
            'ff00::/8',     // multicast
            '2001:db8::/32', // documentation
            ] as $cidr
        ) {
            [$network, $bits] = explode('/', $cidr);
            if (self::ipv6InCidr($binary, (string) inet_pton($network), (int) $bits)) {
                return false;
            }
        }

        return true;
    }

    private static function ipv6InCidr(string $binary, string $network, int $bits): bool
    {
        $fullBytes = intdiv($bits, 8);
        if ($fullBytes > 0 && substr($binary, 0, $fullBytes) !== substr($network, 0, $fullBytes)) {
            return false;
        }

        $remaining = $bits % 8;
        if ($remaining === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remaining)) & 0xff;

        return (ord($binary[$fullBytes]) & $mask) === (ord($network[$fullBytes]) & $mask);
    }
}
