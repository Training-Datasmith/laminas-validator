<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function array_reverse;
use function array_shift;
use function assert;
use function end;
use function explode;
use function implode;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Abstract_Validator;
use Laminas\Validator\Exception\InvalidArgumentException;
use function sprintf;
use function strtolower;
use function trim;
/**
 * Validator for the file extension of a file
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
final class Extension extends Abstract_Validator
{
    public const FALSE_EXTENSION = 'fileExtensionFalse';
    public const NOT_FOUND = 'fileExtensionNotFound';
    public const ERROR_INVALID_TYPE = 'fileExtensionInvalidType';
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
        $this->extensions = self::resolve_extension_list($options['extension'] ?? []);
        $this->extension_list = implode(', ', $this->extensions);
        unset($options['case'], $options['allowNonExistentFile'], $options['extension']);
        parent::__construct($options);
    }
    /**
     * Returns true if and only if the file extension of $value is included in the
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
        $extensions = self::list_possible_file_name_extensions($file_name);
        if (!self::extension_found_in_list($this->extensions, $extensions, $this->case_sensitive)) {
            $this->error(self::FALSE_EXTENSION);
            return false;
        }
        return true;
    }
    /**
     * Parse the `extension` option to a list of non-empty strings
     *
     * @internal
     *
     * @param string|array<array-key, string> $extensions
     * @return non-empty-list<non-empty-string>
     *
     * @psalm-pure
     *
     * @throws InvalidArgumentException If the argument resolves to an empty list.
     */
    public static function resolve_extension_list(string|array $extensions): array
    {
        $extensions = is_string($extensions) ? explode(',', $extensions) : $extensions;
        $list = [];
        foreach ($extensions as $ext) {
            $ext = trim($ext);
            if ($ext === '') {
                continue;
            }
            $list[] = $ext;
        }
        if ($list === []) {
            throw new InvalidArgumentException('The extension option must resolve to a non-empty list');
        }
        return $list;
    }
    /**
     * Return a list of possible filename extensions based on the filename provided
     *
     * For example, a file name such as `foo.bing.tar.gz` will return the list ['gz', 'tar.gz', 'bing.tar.gz']
     *
     * @internal
     *
     * @param non-empty-string $fileName
     * @return list<non-empty-string>
     *
     * @psalm-pure
     */
    public static function list_possible_file_name_extensions(string $file_name): array
    {
        $parts = explode('.', $file_name);
        array_shift($parts);
        $list = [];
        foreach (array_reverse($parts) as $part) {
            if ($part === '') {
                continue;
            }
            $ext = end($list);
            $list[] = $ext !== false ? sprintf('%s.%s', $part, $ext) : $part;
        }
        return $list;
    }
    /**
     * Determine whether any of the `$extensions` are present in `$list`
     *
     * @internal
     *
     * @param non-empty-list<non-empty-string> $list
     * @param list<non-empty-string> $extensions
     *
     * @psalm-pure
     */
    public static function extension_found_in_list(array $list, array $extensions, bool $case_sensitive): bool
    {
        foreach ($extensions as $ext) {
            foreach ($list as $match) {
                if ($case_sensitive && $ext === $match) {
                    return true;
                }
                if (!$case_sensitive && strtolower($ext) === strtolower($match)) {
                    return true;
                }
            }
        }
        return false;
    }
}