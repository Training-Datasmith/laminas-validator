<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Abstract_Validator;
use Laminas\Validator\Exception\InvalidArgumentException;
use function trim;
/**
 * Validator for the mime type of a file
 *
 * @psalm-type OptionsArgument = array{
 *     mimeType?: string|list<string>,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
class Mime_Type extends Abstract_Validator
{
    /** @var string */
    public const FALSE_TYPE = 'fileMimeTypeFalse';
    /** @var string */
    public const NOT_DETECTED = 'fileMimeTypeNotDetected';
    /** @var string */
    public const NOT_READABLE = 'fileMimeTypeNotReadable';
    /** @var array<string, string> */
    protected array $message_templates = [self::FALSE_TYPE => "File has an incorrect mimetype of '%type%'", self::NOT_DETECTED => 'The mimetype could not be detected from the file', self::NOT_READABLE => 'File is not readable or does not exist'];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['type' => 'type'];
    /** Possibly the detected mime-type of the validated file */
    protected ?string $type = null;
    /**
     * A list of acceptable mime-types
     *
     * @var list<string>
     */
    protected readonly array $mime_types;
    /**
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $mime_type = $options['mimeType'] ?? null;
        $this->mime_types = $this->resolve_mime_type($mime_type);
        unset($options['mimeType']);
        parent::__construct($options);
    }
    /**
     * Returns true if the mimetype of the file matches the given ones. Also parts
     * of mimetypes can be checked. If you give for example "image" all image
     * mime types will be accepted like "image/gif", "image/jpeg" and so on.
     */
    public function is_valid(mixed $value): bool
    {
        if (!File_Information::is_possible_file($value)) {
            $this->error(static::NOT_READABLE);
            return false;
        }
        $file_info = File_Information::factory($value);
        $this->set_value($file_info->path);
        if (!$file_info->readable) {
            $this->error(static::NOT_READABLE);
            return false;
        }
        $this->type = $file_info->detect_mime_type();
        if (in_array($this->type, $this->mime_types, true)) {
            return true;
        }
        $types = explode('/', $this->type);
        $types = array_merge($types, explode('-', $this->type));
        $types = array_merge($types, explode(';', $this->type));
        foreach ($this->mime_types as $mime) {
            if (in_array($mime, $types)) {
                return true;
            }
        }
        $this->error(static::FALSE_TYPE);
        return false;
    }
    /**
     * Resolve the mime-type argument from a string or an array
     *
     * @param string|list<string>|null $types
     * @return list<string>
     * @throws InvalidArgumentException When the mime-type list is empty.
     */
    protected function resolve_mime_type(string|array|null $types): array
    {
        if ($types === [] || $types === '' || $types === null) {
            throw new InvalidArgumentException('The `mimeType` option is required and must be a string, or a list of strings');
        }
        if (is_string($types)) {
            $types = explode(',', $types);
        }
        return array_values(array_unique(array_filter(array_map(trim(...), $types))));
    }
}