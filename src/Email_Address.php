<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function array_combine;
use function array_filter;
use const ARRAY_FILTER_USE_BOTH;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function arsort;
use function checkdnsrr;
use function gethostbynamel;
use function getmxrr;
use function idn_to_ascii;
use const INTL_IDNA_VARIANT_UTS46;
use function is_array;
use function is_string;
use Laminas\Translator\Translator_Interface;
use function preg_match;
use function str_contains;
use function strlen;
use function trim;
use U_Converter;
/**
 * @psalm-type Options = array{
 *     useMxCheck?: bool,
 *     useDeepMxCheck?: bool,
 *     useDomainCheck?: bool,
 *     allow?: int-mask-of<Hostname::ALLOW_*>,
 *     strict?: bool,
 *     hostnameValidator?: Hostname|null,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Email_Address extends Abstract_Validator
{
    public const INVALID = 'emailAddressInvalid';
    public const INVALID_FORMAT = 'emailAddressInvalidFormat';
    public const INVALID_HOSTNAME = 'emailAddressInvalidHostname';
    public const INVALID_MX_RECORD = 'emailAddressInvalidMxRecord';
    public const INVALID_SEGMENT = 'emailAddressInvalidSegment';
    public const DOT_ATOM = 'emailAddressDotAtom';
    public const QUOTED_STRING = 'emailAddressQuotedString';
    public const INVALID_LOCAL_PART = 'emailAddressInvalidLocalPart';
    public const LENGTH_EXCEEDED = 'emailAddressLengthExceeded';
    // phpcs:disable Generic.Files.LineLength.TooLong
    /** @var array<string, string> */
    protected array $message_templates = [self::INVALID => 'Invalid type given. String expected', self::INVALID_FORMAT => 'The input is not a valid email address. Use the basic format local-part@hostname', self::INVALID_HOSTNAME => "'%hostname%' is not a valid hostname for the email address", self::INVALID_MX_RECORD => "'%hostname%' does not appear to have any valid MX or A records for the email address", self::INVALID_SEGMENT => "'%hostname%' is not in a routable network segment. The email address should not be resolved from public network", self::DOT_ATOM => "'%localPart%' can not be matched against dot-atom format", self::QUOTED_STRING => "'%localPart%' can not be matched against quoted-string format", self::INVALID_LOCAL_PART => "'%localPart%' is not a valid local part for the email address", self::LENGTH_EXCEEDED => 'The input exceeds the allowed length'];
    // phpcs:enable
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['hostname' => 'hostname', 'localPart' => 'localPart'];
    protected ?string $hostname = null;
    protected ?string $local_part = null;
    private readonly Hostname $hostname_validator;
    private readonly bool $use_mx_check;
    private readonly bool $use_deep_mx_check;
    private readonly bool $use_domain_check;
    private readonly bool $strict;
    /**
     * Instantiates hostname validator for local use
     *
     * The following additional option keys are supported:
     * 'hostnameValidator' => A hostname validator, see Laminas\Validator\Hostname
     * 'allow'             => Options for the hostname validator, see Laminas\Validator\Hostname::ALLOW_*
     * 'strict'            => Whether to adhere to strictest requirements in the spec
     * 'useMxCheck'        => If MX check should be enabled, boolean
     * 'useDeepMxCheck'    => If a deep MX check should be done, boolean
     *
     * @param Options $options
     */
    public function __construct(array $options = [])
    {
        $messages = $options['messages'] ?? [];
        $hostname_messages = array_filter($messages, fn(string $value, string $key): bool => !array_key_exists($key, $this->message_templates), ARRAY_FILTER_USE_BOTH);
        $messages = array_filter($messages, fn(string $value, string $key): bool => array_key_exists($key, $this->message_templates), ARRAY_FILTER_USE_BOTH);
        $allow = $options['allow'] ?? Hostname::ALLOW_DNS;
        $this->hostname_validator = $options['hostnameValidator'] ?? new Hostname(['allow' => $allow, 'messages' => $hostname_messages]);
        $this->use_mx_check = $options['useMxCheck'] ?? false;
        $this->use_deep_mx_check = $options['useDeepMxCheck'] ?? false;
        $this->use_domain_check = $options['useDomainCheck'] ?? true;
        $this->strict = $options['strict'] ?? true;
        unset($options['allow'], $options['hostnameValidator'], $options['useMxCheck'], $options['useDeepMxCheck'], $options['useDomainCheck'], $options['strict']);
        $options['messages'] = $messages;
        parent::__construct($options);
    }
    /**
     * Overrides `setMessage` of AbstractValidator so that messages propagate to the composed hostname validator
     *
     * @inheritDoc
     */
    public function set_message(string $message_string, ?string $message_key = null): void
    {
        if ($message_key === null) {
            $this->hostname_validator->set_message($message_string);
            parent::set_message($message_string);
        }
        if (!isset($this->message_templates[$message_key])) {
            $this->hostname_validator->set_message($message_string, $message_key);
        } else {
            parent::set_message($message_string, $message_key);
        }
    }
    /**
     * Returns whether the given host is a reserved IP, or a hostname that resolves to a reserved IP
     */
    private function is_reserved(string $host): bool
    {
        $validator = new Host_With_Public_I_Pv4address();
        return !$validator->is_valid($host);
    }
    /**
     * Internal method to validate the local part of the email address
     */
    private function validate_local_part(string $local_part): bool
    {
        // First try to match the local part on the common dot-atom format
        // Dot-atom characters are: 1*atext *("." 1*atext)
        // atext: ALPHA / DIGIT / and "!", "#", "$", "%", "&", "'", "*",
        //        "+", "-", "/", "=", "?", "^", "_", "`", "{", "|", "}", "~"
        $atext = 'a-zA-Z0-9\x21\x23\x24\x25\x26\x27\x2a\x2b\x2d\x2f\x3d\x3f\x5e\x5f\x60\x7b\x7c\x7d\x7e';
        if (preg_match('/^[' . $atext . ']+(\x2e+[' . $atext . ']+)*$/', $local_part)) {
            return true;
        }
        if ($this->validate_internationalized_local_part($local_part)) {
            return true;
        }
        // Try quoted string format (RFC 5321 Chapter 4.1.2)
        // Quoted-string characters are: DQUOTE *(qtext/quoted-pair) DQUOTE
        $qtext = '\x20-\x21\x23-\x5b\x5d-\x7e';
        // %d32-33 / %d35-91 / %d93-126
        $quoted_pair = '\x20-\x7e';
        // %d92 %d32-126
        if (preg_match('/^"([' . $qtext . ']|\x5c[' . $quoted_pair . '])*"$/', $local_part)) {
            return true;
        }
        $this->error(self::DOT_ATOM);
        $this->error(self::QUOTED_STRING);
        $this->error(self::INVALID_LOCAL_PART);
        return false;
    }
    /**
     * @param string $localPart Address local part to validate.
     */
    protected function validate_internationalized_local_part(string $local_part): bool
    {
        if (U_Converter::transcode($local_part, 'UTF-8', 'UTF-8') === false) {
            // invalid utf?
            return false;
        }
        $atext = 'a-zA-Z0-9\x21\x23\x24\x25\x26\x27\x2a\x2b\x2d\x2f\x3d\x3f\x5e\x5f\x60\x7b\x7c\x7d\x7e';
        // RFC 6532 extends atext to include non-ascii utf
        // @see https://tools.ietf.org/html/rfc6532#section-3.1
        $uatext = $atext . '\x{80}-\x{FFFF}';
        return (bool) preg_match('/^[' . $uatext . ']+(\x2e+[' . $uatext . ']+)*$/u', $local_part);
    }
    /**
     * Internal method to validate the servers MX records
     */
    protected function validate_mx_records(string $hostname): bool
    {
        $mx_hosts = [];
        $weight = [];
        $mx_record = [];
        $result = getmxrr($hostname, $mx_hosts, $weight);
        if ($result) {
            $mx_record = array_combine($mx_hosts, $weight) ?: [];
            arsort($mx_record);
        }
        // Fallback to IPv4 hosts if no MX record found (RFC 2821 SS 5).
        if (!$result) {
            $result = gethostbynamel($hostname);
            if (is_array($result)) {
                $mx_record = array_flip($result);
            }
        }
        if ($result === false) {
            $this->error(self::INVALID_MX_RECORD);
            return false;
        }
        if (!$this->use_deep_mx_check) {
            return true;
        }
        $valid_address = false;
        $reserved = true;
        foreach (array_keys($mx_record) as $mx_host) {
            $res = $this->is_reserved($mx_host);
            if (!$res) {
                $reserved = false;
            }
            if (trim($mx_host) === '') {
                continue;
            }
            if (!$res && (checkdnsrr($mx_host, 'A') || checkdnsrr($mx_host, 'AAAA') || checkdnsrr($mx_host, 'A6'))) {
                $valid_address = true;
                break;
            }
        }
        if (!$valid_address) {
            $error = $reserved ? self::INVALID_SEGMENT : self::INVALID_MX_RECORD;
            $this->error($error);
            return false;
        }
        return true;
    }
    /**
     * Internal method to validate the hostname part of the email address
     */
    private function validate_hostname_part(string $hostname): bool
    {
        $this->hostname_validator->set_translator($this->get_translator());
        $is_valid = $this->hostname_validator->is_valid($hostname);
        if (!$is_valid) {
            $this->error(self::INVALID_HOSTNAME);
            // Get messages and errors from hostnameValidator
            foreach ($this->hostname_validator->get_messages() as $code => $message) {
                $this->error_messages[$code] = $message;
            }
            return false;
        }
        if ($this->use_mx_check) {
            // MX check on hostname
            return $this->validate_mx_records($hostname);
        }
        return $is_valid;
    }
    /**
     * Splits the given value in hostname and local part of the email address
     *
     * @return array{localPart: string, hostname: string}|false Returns false when the email can not be split
     */
    private static function split_email_parts(string $value): array|false
    {
        // Split email address up and disallow '..'
        if (str_contains($value, '..') || !preg_match('/^(.+)@([^@]+)$/', $value, $matches)) {
            return false;
        }
        return ['localPart' => $matches[1], 'hostname' => self::idn_to_ascii($matches[2])];
    }
    /**
     * Defined by Laminas\Validator\ValidatorInterface
     *
     * Returns true if and only if $value is a valid email address
     * according to RFC2822
     *
     * @link   http://www.ietf.org/rfc/rfc2822.txt RFC2822
     * @link   http://www.columbia.edu/kermit/ascii.html US-ASCII characters
     */
    public function is_valid(mixed $value): bool
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $length = true;
        $this->set_value($value);
        // Split email address up and disallow '..'
        $split = self::split_email_parts($value);
        if ($split === false) {
            $this->error(self::INVALID_FORMAT);
            return false;
        }
        ['localPart' => $local_part, 'hostname' => $hostname] = $split;
        $this->local_part = $local_part;
        $this->hostname = $hostname;
        if ($this->strict && strlen($local_part) > 64 || strlen($hostname) > 255) {
            $length = false;
            $this->error(self::LENGTH_EXCEEDED);
        }
        // Match hostname part
        $hostname_valid = false;
        if ($this->use_domain_check) {
            $hostname_valid = $this->validate_hostname_part($hostname);
        }
        $local = $this->validate_local_part($local_part);
        // If both parts valid, return true
        return $local && $length && (!$this->use_domain_check || $hostname_valid !== false);
    }
    /**
     * Safely convert UTF-8 encoded domain name to ASCII
     *
     * @param string $hostname the UTF-8 encoded email
     */
    private static function idn_to_ascii(string $hostname): string
    {
        $value = idn_to_ascii($hostname, 0, INTL_IDNA_VARIANT_UTS46);
        return $value !== false ? $value : $hostname;
    }
}