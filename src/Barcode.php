<?php

declare (strict_types=1);
namespace Laminas\Validator;

use function class_exists;
use function implode;
use function is_a;
use function is_array;
use function is_string;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Barcode\Adapter_Interface;
use Laminas\Validator\Barcode\Ean13;
use Laminas\Validator\Exception\InvalidArgumentException;
use function sprintf;
use function strtolower;
use function ucfirst;
/**
 * @psalm-type OptionsArgument = array{
 *     adapter?: AdapterInterface|class-string<AdapterInterface>|string,
 *     useChecksum?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 * @psalm-import-type AllowedLength from AdapterInterface
 */
final class Barcode extends Abstract_Validator
{
    public const INVALID = 'barcodeInvalid';
    public const FAILED = 'barcodeFailed';
    public const INVALID_CHARS = 'barcodeInvalidChars';
    public const INVALID_LENGTH = 'barcodeInvalidLength';
    /** @var array<string, string> */
    protected array $message_templates = [self::FAILED => 'The input failed checksum validation', self::INVALID_CHARS => 'The input contains invalid characters', self::INVALID_LENGTH => 'The input should have a length of %length% characters', self::INVALID => 'Invalid type given. String expected'];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['length' => 'length'];
    private readonly Adapter_Interface $adapter;
    protected readonly string $length;
    private readonly bool $use_checksum;
    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        $this->adapter = $this->resolve_adapter($options['adapter'] ?? new Ean13());
        $this->length = $this->stringify_expected_length();
        $this->use_checksum = $options['useChecksum'] ?? false;
        parent::__construct($options);
    }
    private function resolve_adapter(string|Adapter_Interface $adapter): Adapter_Interface
    {
        if (is_string($adapter) && !class_exists($adapter)) {
            $adapter = sprintf('Laminas\Validator\Barcode\%s', ucfirst(strtolower($adapter)));
        }
        if (is_string($adapter) && is_a($adapter, Adapter_Interface::class, true)) {
            $adapter = new $adapter();
        }
        if (!$adapter instanceof Adapter_Interface) {
            throw new InvalidArgumentException(sprintf('The "adapter" option must resolve to an instance of %s', Adapter_Interface::class));
        }
        return $adapter;
    }
    private function stringify_expected_length(): string
    {
        $length = $this->adapter->get_length();
        if (is_array($length)) {
            $length = implode('/', $length);
        }
        return (string) $length;
    }
    /**
     * Defined by Laminas\Validator\ValidatorInterface
     *
     * Returns true if and only if $value contains a valid barcode
     */
    public function is_valid(mixed $value): bool
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }
        $this->set_value($value);
        if (!$this->adapter->has_valid_length($value)) {
            $this->error(self::INVALID_LENGTH);
            return false;
        }
        if (!$this->adapter->has_valid_characters($value)) {
            $this->error(self::INVALID_CHARS);
            return false;
        }
        if ($this->use_checksum && !$this->adapter->has_valid_checksum($value)) {
            $this->error(self::FAILED);
            return false;
        }
        return true;
    }
}