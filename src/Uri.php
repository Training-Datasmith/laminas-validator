<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function is_array;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Exception\InvalidArgumentException;
use function parse_url;
/**
 * @psalm-type Options = array{
 *     allowRelative?: bool,
 *     allowAbsolute?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Uri extends Abstract_Validator
{
    public const INVALID = 'uriInvalid';
    public const NOT_URI = 'notUri';
    public const NOT_ABSOLUTE = 'notAbsoluteUri';
    public const NOT_RELATIVE = 'notRelativeUri';
    /** @var array<string, string> */
    protected array $message_templates = [self::INVALID => 'Invalid type given. String expected', self::NOT_URI => 'The input does not appear to be a valid Uri', self::NOT_ABSOLUTE => 'Expected an absolute uri but a relative uri was received', self::NOT_RELATIVE => 'Expected a relative uri but an absolute uri was received'];
    private readonly bool $allow_relative;
    private readonly bool $allow_absolute;
    /** @param Options $options */
    public function __construct(array $options = [])
    {
        $this->allow_relative = $options['allowRelative'] ?? true;
        $this->allow_absolute = $options['allowAbsolute'] ?? true;
        if ($this->allow_relative === false && $this->allow_absolute === false) {
            throw new InvalidArgumentException('Disallowing both relative and absolute uris means that no uris will be valid');
        }
        parent::__construct($options);
    }
    /**
     * Returns true if and only if $value validates as a Uri
     */
    public function is_valid(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            $this->error(self::INVALID);
            return false;
        }
        $parts = parse_url($value);
        if (!is_array($parts)) {
            $this->error(self::NOT_URI);
            return false;
        }
        if (!$this->allow_relative && $this->allow_absolute) {
            if (!isset($parts['host'])) {
                $this->error(self::NOT_ABSOLUTE);
                return false;
            }
        }
        if (!$this->allow_absolute && $this->allow_relative) {
            if (isset($parts['host'])) {
                $this->error(self::NOT_RELATIVE);
                return false;
            }
        }
        return true;
    }
}