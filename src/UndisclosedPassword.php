<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function array_filter;
use function explode;
use function is_string;
use Psr\Http\Client\Client_Exception_Interface;
use Psr\Http\Client\Client_Interface;
use Psr\Http\Message\Request_Factory_Interface;
use Sensitive_Parameter;
use function sha1;
use function strcmp;
use function strtoupper;
use function substr;
final class Undisclosed_Password extends Abstract_Validator
{
    // phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
    private const HIBP_API_URI = 'https://api.pwnedpasswords.com';
    private const HIBP_K_ANONYMITY_HASH_RANGE_LENGTH = 5;
    private const HIBP_K_ANONYMITY_HASH_RANGE_BASE = 0;
    // phpcs:enable
    private const PASSWORD_BREACHED = 'passwordBreached';
    private const NOT_A_STRING = 'wrongInput';
    // phpcs:disable Generic.Files.LineLength.TooLong
    /** @var array<string, string> */
    protected array $message_templates = [self::PASSWORD_BREACHED => 'The provided password was found in previous breaches, please create another password', self::NOT_A_STRING => 'The provided password is not a string, please provide a correct password'];
    // phpcs:enable
    public function __construct(private readonly Client_Interface $http_client, private readonly Request_Factory_Interface $make_http_request)
    {
        parent::__construct();
    }
    /** {@inheritDoc} */
    public function is_valid(
        #[Sensitive_Parameter]
        mixed $value
    ): bool
    {
        if (!is_string($value)) {
            $this->error(self::NOT_A_STRING);
            return false;
        }
        if ($this->is_pwned_password($value)) {
            $this->error(self::PASSWORD_BREACHED);
            return false;
        }
        return true;
    }
    private function is_pwned_password(
        #[Sensitive_Parameter]
        string $password
    ): bool
    {
        $sha1Hash = $this->hash_password($password);
        $range_hash = $this->get_range_hash($sha1Hash);
        $hash_list = $this->retrieve_hash_list($range_hash);
        return $this->hash_in_response($sha1Hash, $hash_list);
    }
    /**
     * We use a SHA1 hashed password for checking it against
     * the breached data set of HIBP.
     */
    private function hash_password(
        #[Sensitive_Parameter]
        string $password
    ): string
    {
        $hashed_password = sha1($password);
        return strtoupper($hashed_password);
    }
    /**
     * Creates a hash range that will be send to HIBP API
     * applying K-Anonymity
     *
     * @see https://www.troyhunt.com/enhancing-pwned-passwords-privacy-by-exclusively-supporting-anonymity/
     */
    private function get_range_hash(
        #[Sensitive_Parameter]
        string $password_hash
    ): string
    {
        return substr($password_hash, self::HIBP_K_ANONYMITY_HASH_RANGE_BASE, self::HIBP_K_ANONYMITY_HASH_RANGE_LENGTH);
    }
    /**
     * Making a connection to the HIBP API to retrieve a
     * list of hashes that all have the same range as we
     * provided.
     *
     * @throws ClientExceptionInterface
     */
    private function retrieve_hash_list(
        #[Sensitive_Parameter]
        string $password_range
    ): string
    {
        $request = $this->make_http_request->create_request('GET', self::HIBP_API_URI . '/range/' . $password_range);
        $response = $this->http_client->send_request($request);
        return (string) $response->get_body();
    }
    /**
     * Checks if the password is in the response from HIBP
     */
    private function hash_in_response(
        #[Sensitive_Parameter]
        string $sha1Hash,
        #[Sensitive_Parameter]
        string $result_stream
    ): bool
    {
        $data = explode("\r\n", $result_stream);
        $hashes = array_filter($data, static function ($value) use ($sha1Hash): bool {
            [$hash] = explode(':', $value);
            return strcmp($hash, substr($sha1Hash, self::HIBP_K_ANONYMITY_HASH_RANGE_LENGTH)) === 0;
        });
        return $hashes !== [];
    }
}