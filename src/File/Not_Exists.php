<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function array_filter;
use function array_map;
use function array_values;
use const DIRECTORY_SEPARATOR;
use function explode;
use function file_exists;
use function implode;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Abstract_Validator;
use function ltrim;
use function rtrim;
use function sprintf;
use function trim;
/**
 * Validator which checks if the destination file does not exist
 *
 * @psalm-type OptionsArgument = array{
 *     directory?: string|list<string>,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 *  }
 */
final class Not_Exists extends Abstract_Validator
{
    public const DOES_EXIST = 'fileNotExistsDoesExist';
    /** @var array<string, string> */
    protected array $message_templates = [self::DOES_EXIST => 'File exists'];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['directory' => 'directoriesAsString'];
    protected readonly string $directories_as_string;
    /** @var list<string> */
    private readonly array $directories;
    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $this->directories = $this->resolve_directories($options['directory'] ?? null);
        $this->directories_as_string = implode(', ', $this->directories);
        parent::__construct($options);
    }
    /**
     * Returns true if and only if the file does not exist in the set destinations
     */
    public function is_valid(mixed $value): bool
    {
        if (File_Information::is_possible_file($value)) {
            if ($this->directories === []) {
                $this->error(self::DOES_EXIST);
                return false;
            }
            $file = File_Information::factory($value);
            $value = $file->base_name;
        }
        $this->set_value($value);
        if (!is_string($value)) {
            // Not a file path, therefore it cannot exist.
            return true;
        }
        foreach ($this->directories as $directory) {
            $path = sprintf('%s%s%s', rtrim($directory, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR, ltrim($value, DIRECTORY_SEPARATOR));
            if (file_exists($path)) {
                $this->error(self::DOES_EXIST);
                return false;
            }
        }
        return true;
    }
    /** @return list<string> */
    private function resolve_directories(string|array|null $directories): array
    {
        if ($directories === null || $directories === [] || $directories === '') {
            return [];
        }
        if (is_string($directories)) {
            $directories = explode(',', $directories);
        }
        return array_values(array_filter(array_map(trim(...), $directories)));
    }
}