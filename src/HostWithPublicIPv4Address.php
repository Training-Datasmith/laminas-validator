<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function assert;
use function explode;
use const FILTER_FLAG_GLOBAL_RANGE;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_IP;
use function filter_var;
use function get_debug_type;
use function gethostbynamel;
use function ip2long;
use function is_array;
use function is_int;
use function is_string;
final class Host_With_Public_I_Pv4address extends Abstract_Validator
{
    /**
     * Reserved CIDRs are extracted from IANA with additions from Wikipedia
     *
     * @link https://www.iana.org/assignments/iana-ipv4-special-registry/iana-ipv4-special-registry.xhtml
     * @link https://en.wikipedia.org/wiki/Reserved_IP_addresses
     */
    private const RESERVED_CIDR = [
        '0.0.0.0/8',
        '0.0.0.0/32',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.0.0/29',
        '192.0.0.8/32',
        '192.0.0.9/32',
        '192.0.0.10/32',
        '192.0.0.170/32',
        '192.0.0.171/32',
        '192.0.2.0/24',
        '192.31.196.0/24',
        '192.52.193.0/24',
        '192.88.99.0/24',
        '192.168.0.0/16',
        '192.175.48.0/24',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        // Wikipedia
        '233.252.0.0/24',
        // Wikipedia
        '240.0.0.0/4',
        '255.255.255.255/32',
    ];
    public const ERROR_NOT_STRING = 'hostnameNotString';
    public const ERROR_HOSTNAME_NOT_RESOLVED = 'hostnameNotResolved';
    public const ERROR_PRIVATE_IP_FOUND = 'privateIpAddressFound';
    /** @var array<string, string> */
    protected array $message_templates = [self::ERROR_NOT_STRING => 'Expected a string hostname but received %type%', self::ERROR_HOSTNAME_NOT_RESOLVED => 'The hostname "%value%" cannot be resolved', self::ERROR_PRIVATE_IP_FOUND => 'The hostname "%value%" resolves to at least one reserved IPv4 address'];
    protected string $type = 'null';
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['type' => 'type', 'value' => 'value'];
    public function is_valid(mixed $value): bool
    {
        $this->type = get_debug_type($value);
        if (!is_string($value)) {
            $this->error(self::ERROR_NOT_STRING);
            return false;
        }
        $this->set_value($value);
        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            $address_list = gethostbynamel($value);
        } else {
            $address_list = [$value];
        }
        if (!is_array($address_list)) {
            $this->error(self::ERROR_HOSTNAME_NOT_RESOLVED);
            return false;
        }
        $private_address_was_found = false;
        $filter_flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE;
        foreach ($address_list as $server) {
            /**
             * Initially test with PHP's built-in filter_var features as this will be quicker than checking
             * presence with a CIDR
             */
            if (filter_var($server, FILTER_VALIDATE_IP, $filter_flags) === false) {
                $private_address_was_found = true;
                break;
            }
            if ($this->in_reserved_cidr($server)) {
                $private_address_was_found = true;
                break;
            }
        }
        if ($private_address_was_found) {
            $this->error(self::ERROR_PRIVATE_IP_FOUND);
            return false;
        }
        return true;
    }
    private function in_reserved_cidr(string $ip): bool
    {
        foreach (self::RESERVED_CIDR as $cidr) {
            $cidr = explode('/', $cidr);
            $start_ip = ip2long($cidr[0]);
            assert(is_int($start_ip));
            $end_ip = $start_ip + 2 ** (32 - (int) $cidr[1]) - 1;
            assert(is_int($end_ip));
            $int = ip2long($ip);
            if ($int >= $start_ip && $int <= $end_ip) {
                return true;
            }
        }
        return false;
    }
}