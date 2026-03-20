<?php

declare (strict_types=1);
namespace Laminas\Validator\Sitemap;

use function is_numeric;
use Laminas\Validator\Abstract_Validator;
/**
 * Validates whether a given value is valid as a sitemap <priority> value
 *
 * @link https://www.sitemaps.org/protocol.html Sitemaps XML format
 */
final class Priority extends Abstract_Validator
{
    /**
     * Validation key for not valid
     */
    public const NOT_VALID = 'sitemapPriorityNotValid';
    public const INVALID = 'sitemapPriorityInvalid';
    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $message_templates = [self::NOT_VALID => 'The input is not a valid sitemap priority', self::INVALID => 'Invalid type given. Numeric string, integer or float expected'];
    /**
     * Validates if a string is valid as a sitemap priority
     *
     * @link https://www.sitemaps.org/protocol.html#prioritydef
     */
    public function is_valid(mixed $value): bool
    {
        if (!is_numeric($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $this->set_value($value);
        $value = (float) $value;
        if ($value < 0 || $value > 1) {
            $this->error(self::NOT_VALID);
            return false;
        }
        return true;
    }
}