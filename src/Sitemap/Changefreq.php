<?php

declare (strict_types=1);
namespace Laminas\Validator\Sitemap;

use function in_array;
use function is_string;
use Laminas\Validator\Abstract_Validator;
/**
 * Validates whether a given value is valid as a sitemap <changefreq> value
 *
 * @link https://www.sitemaps.org/protocol.html Sitemaps XML format
 */
final class Changefreq extends Abstract_Validator
{
    /**
     * Validation key for not valid
     */
    public const NOT_VALID = 'sitemapChangefreqNotValid';
    public const INVALID = 'sitemapChangefreqInvalid';
    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $message_templates = [self::NOT_VALID => 'The input is not a valid sitemap changefreq', self::INVALID => 'Invalid type given. String expected'];
    /**
     * Valid change frequencies
     *
     * @var list<string>
     */
    private array $change_freqs = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
    /**
     * Validates if a string is valid as a sitemap changefreq
     *
     * @link https://www.sitemaps.org/protocol.html#changefreqdef
     */
    public function is_valid(mixed $value): bool
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $this->set_value($value);
        if (!in_array($value, $this->change_freqs, true)) {
            $this->error(self::NOT_VALID);
            return false;
        }
        return true;
    }
}