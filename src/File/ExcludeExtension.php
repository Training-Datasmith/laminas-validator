<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function assert;
use function implode;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Abstract_Validator;
/**
 * Validator for the excluding file extensions
 *
 * @psalm-type OptionsArgument = array{
 *     case?: bool,
 *     extension: non-empty-string|list<non-empty-string>,
 *     allowNonExistentFile?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Exclude_Extension extends Abstract_Validator
{
    public const FALSE_EXTENSION = 'fileExcludeExtensionFalse';
    public const NOT_FOUND = 'fileExcludeExtensionNotFound';
    public const ERROR_INVALID_TYPE = 'fileExcludeExtensionInvalidType';
    /** @var array<string, string> */
    protected array $message_templates = [self::FALSE_EXTENSION => 'File has an incorrect extension', self::NOT_FOUND => 'File is not readable or does not exist', self::ERROR_INVALID_TYPE => 'The value is neither a file, nor a string'];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['extension' => 'extensionList'];
    private readonly bool $case_sensitive;
    private readonly bool $allow_non_existent_file;
    /** @var non-empty-list<non-empty-string> */
    private readonly array $extensions;
    /**
     * A CSV list of allowed extensions for error messages
     *
     * @var non-empty-string
     */
    protected readonly string $extension_list;
    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options)
    {
        $this->case_sensitive = $options['case'] ?? false;
        $this->allow_non_existent_file = $options['allowNonExistentFile'] ?? false;
        $this->extensions = Extension::resolve_extension_list($options['extension'] ?? []);
        $this->extension_list = implode(', ', $this->extensions);
        unset($options['case'], $options['allowNonExistentFile'], $options['extension']);
        parent::__construct($options);
    }
    /**
     * Returns true if and only if the file extension of $value is not included in the
     * set extension list
     */
    public function is_valid(mixed $value): bool
    {
        $this->set_value($value);
        $is_file = File_Information::is_possible_file($value);
        if (!$is_file && !$this->allow_non_existent_file) {
            $this->error(self::NOT_FOUND);
            return false;
        }
        if (!$is_file && !is_string($value) || $value === '') {
            $this->error(self::ERROR_INVALID_TYPE);
            return false;
        }
        if ($is_file) {
            $file = File_Information::factory($value);
            $file_name = $file->client_file_name ?? $file->base_name;
        } else {
            $file_name = $value;
        }
        assert($file_name !== '');
        $this->value = $file_name;
        $extensions = Extension::list_possible_file_name_extensions($file_name);
        if (Extension::extension_found_in_list($this->extensions, $extensions, $this->case_sensitive)) {
            $this->error(self::FALSE_EXTENSION);
            return false;
        }
        return true;
    }
}