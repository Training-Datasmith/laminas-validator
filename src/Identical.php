<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function is_array;
use function is_int;
use function is_string;
use function key;
use Laminas\Translator\Translator_Interface;
use function var_export;
/**
 * @psalm-type OptionsArgument = array{
 *     token?: mixed,
 *     strict?: bool,
 *     literal?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Identical extends Abstract_Validator
{
    public const NOT_SAME = 'notSame';
    public const MISSING_TOKEN = 'missingToken';
    /** @var array<string, string> */
    protected array $message_templates = [self::NOT_SAME => 'The two given tokens do not match', self::MISSING_TOKEN => 'No token was provided to match against'];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['token' => 'tokenString'];
    protected readonly ?string $token_string;
    private readonly mixed $token;
    private readonly bool $strict;
    private readonly bool $literal;
    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        /** @psalm-suppress MixedAssignment $token */
        $token = $options['token'] ?? null;
        $strict = $options['strict'] ?? true;
        $literal = $options['literal'] ?? false;
        unset($options['token'], $options['strict'], $options['literal']);
        $this->token = $token;
        $this->token_string = is_array($token) ? var_export($token, true) : (string) $token;
        $this->strict = $strict;
        $this->literal = $literal;
        parent::__construct($options);
    }
    /**
     * Returns true if and only if a token has been set and the provided value
     * matches that token.
     *
     * @psalm-suppress MixedAssignment, MixedArrayAccess Tokens are mixed as are array members
     */
    public function is_valid(mixed $value, ?array $context = null): bool
    {
        $this->set_value($value);
        if ($this->token === null) {
            $this->error(self::MISSING_TOKEN);
            return false;
        }
        $match_to = $this->token;
        if (!$this->literal && $context !== null) {
            if (is_array($match_to)) {
                while (is_array($match_to)) {
                    $key = key($match_to);
                    if ($key === null || !isset($context[$key])) {
                        break;
                    }
                    $context = $context[$key];
                    $match_to = $match_to[$key];
                }
            }
            // if $matchTo is an array it means the above loop didn't go all the way down to the leaf,
            // so the $matchTo structure doesn't match the $context structure
            if (is_array($match_to) || !is_int($match_to) && !is_string($match_to) || !isset($context[$match_to])) {
                $match_to = $this->token;
            } else {
                $match_to = $context[$match_to] ?? null;
            }
        }
        if ($this->strict && $value !== $match_to || !$this->strict && $value != $match_to) {
            $this->error(self::NOT_SAME);
            return false;
        }
        return true;
    }
}