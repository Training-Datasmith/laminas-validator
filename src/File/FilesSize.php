<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function in_array;
use function is_array;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Abstract_Validator;
use Laminas\Validator\Exception\InvalidArgumentException;
use Psr\Http\Message\Uploaded_File_Interface;
/**
 * Validate the cumulative size of multiple files
 *
 * @psalm-type OptionsArgument = array{
 *     min?: string|numeric|null,
 *     max?: string|numeric|null,
 *     useByteString?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Files_Size extends Abstract_Validator
{
    public const TOO_BIG = 'fileFilesSizeTooBig';
    public const TOO_SMALL = 'fileFilesSizeTooSmall';
    public const NOT_READABLE = 'fileFilesSizeNotReadable';
    /** @var array<string, string> */
    protected array $message_templates = [self::TOO_BIG => "All files in sum should have a maximum size of '%max%' but '%size%' were detected", self::TOO_SMALL => "All files in sum should have a minimum size of '%min%' but '%size%' were detected", self::NOT_READABLE => 'One or more files can not be read'];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['min' => 'minString', 'max' => 'maxString', 'size' => 'size'];
    /**
     * Detected size
     */
    protected string $size = '';
    protected readonly string $min_string;
    protected readonly string $max_string;
    protected readonly int|null $min;
    protected readonly int|null $max;
    private readonly bool $use_byte_string;
    /**
     * Sets validator options
     *
     * $options accepts the following keys:
     * 'min': Minimum file size
     * 'max': Maximum file size
     * 'useByteString': Use bytestring or real size for messages
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;
        $this->use_byte_string = $options['useByteString'] ?? true;
        if ($min === null && $max === null) {
            throw new InvalidArgumentException('One of `min` or `max` options are required');
        }
        if (is_string($min)) {
            $min = Bytes::from_si_unit($min)->bytes;
        }
        if (is_string($max)) {
            $max = Bytes::from_si_unit($max)->bytes;
        }
        $this->min = $min !== null ? (int) $min : null;
        $this->max = $max !== null ? (int) $max : null;
        if ($this->min !== null && $this->max !== null && $this->min > $this->max) {
            throw new InvalidArgumentException('The `min` option cannot exceed the `max` option');
        }
        unset($options['min'], $options['max'], $options['useByteString']);
        $this->min_string = $this->min !== null && $this->use_byte_string ? Bytes::from_integer($this->min)->to_si_unit() : (string) $this->min;
        $this->max_string = $this->max !== null && $this->use_byte_string ? Bytes::from_integer($this->max)->to_si_unit() : (string) $this->max;
        parent::__construct($options);
    }
    /**
     * Returns true if and only if the disk usage of all files is at least min and
     * not bigger than max (when max is not null).
     */
    public function is_valid(mixed $value): bool
    {
        if (is_array($value) && isset($value['tmp_name'])) {
            $value = [$value];
        }
        if (is_string($value) || $value instanceof Uploaded_File_Interface) {
            $value = [$value];
        }
        $paths = [];
        $size = 0;
        /** @psalm-suppress MixedAssignment $possibleFile - Yep. This is `mixed` */
        foreach ($value as $possible_file) {
            if (!File_Information::is_possible_file($possible_file)) {
                $this->error(self::NOT_READABLE);
                return false;
            }
            $file = File_Information::factory($possible_file);
            // Is file readable ?
            if (!$file->readable) {
                $this->error(self::NOT_READABLE);
                return false;
            }
            if (in_array($file->path, $paths, true)) {
                continue;
                // Skip duplicate entries
            }
            $paths[] = $file->path;
            $size += $file->size()->bytes;
        }
        $this->size = $this->use_byte_string ? Bytes::from_integer($size)->to_si_unit() : (string) $size;
        if ($this->min !== null && $size < $this->min) {
            $this->error(self::TOO_SMALL);
            return false;
        }
        if ($this->max !== null && $size > $this->max) {
            $this->error(self::TOO_BIG);
            return false;
        }
        return true;
    }
}