<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function count;
use function getimagesize;
use Laminas\Translator\Translator_Interface;
use Laminas\Validator\Abstract_Validator;
use Laminas\Validator\Exception\InvalidArgumentException;
/**
 * Validator for the image size of an image file
 *
 * @psalm-type OptionsArgument = array{
 *     minWidth?: int|null,
 *     maxWidth?: int|null,
 *     minHeight?: int|null,
 *     maxHeight?: int|null,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Image_Size extends Abstract_Validator
{
    /**
     * @const string Error constants
     */
    public const WIDTH_TOO_BIG = 'fileImageSizeWidthTooBig';
    public const WIDTH_TOO_SMALL = 'fileImageSizeWidthTooSmall';
    public const HEIGHT_TOO_BIG = 'fileImageSizeHeightTooBig';
    public const HEIGHT_TOO_SMALL = 'fileImageSizeHeightTooSmall';
    public const NOT_DETECTED = 'fileImageSizeNotDetected';
    public const NOT_READABLE = 'fileImageSizeNotReadable';
    /** @var array<string, string> */
    protected array $message_templates = [self::WIDTH_TOO_BIG => "Maximum allowed width for image should be '%maxwidth%' but '%width%' detected", self::WIDTH_TOO_SMALL => "Minimum expected width for image should be '%minwidth%' but '%width%' detected", self::HEIGHT_TOO_BIG => "Maximum allowed height for image should be '%maxheight%' but '%height%' detected", self::HEIGHT_TOO_SMALL => "Minimum expected height for image should be '%minheight%' but '%height%' detected", self::NOT_DETECTED => 'The size of image could not be detected', self::NOT_READABLE => 'File is not readable or does not exist'];
    /** @var array<string, string|array<string, string>> */
    protected array $message_variables = ['minwidth' => 'minWidth', 'maxwidth' => 'maxWidth', 'minheight' => 'minHeight', 'maxheight' => 'maxHeight', 'width' => 'width', 'height' => 'height'];
    /**
     * Detected width
     */
    protected int|null $width;
    /**
     * Detected height
     */
    protected int|null $height;
    protected readonly int $min_width;
    protected readonly int|null $max_width;
    protected readonly int $min_height;
    protected readonly int|null $max_height;
    /**
     * Sets validator options
     *
     * Accepts the following option keys:
     * - minHeight
     * - minWidth
     * - maxHeight
     * - maxWidth
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options)
    {
        $min_width = $options['minWidth'] ?? 0;
        $max_width = $options['maxWidth'] ?? null;
        $min_height = $options['minHeight'] ?? 0;
        $max_height = $options['maxHeight'] ?? null;
        if ($min_width === 0 && $max_width === null && $min_height === 0 && $max_height === null) {
            throw new InvalidArgumentException('At least one size constraint is required');
        }
        if ($min_width > (int) $max_width || $min_height > (int) $max_height) {
            throw new InvalidArgumentException('Max width or height must exceed the minimum equivalent');
        }
        $this->min_width = $min_width;
        $this->max_width = $max_width;
        $this->min_height = $min_height;
        $this->max_height = $max_height;
        $this->width = null;
        $this->height = null;
        unset($options['minWidth'], $options['maxWidth'], $options['minHeight'], $options['maxHeight']);
        parent::__construct($options);
    }
    /**
     * Returns true if and only if the image size of $value is at least min and
     * not bigger than max
     */
    public function is_valid(mixed $value): bool
    {
        $this->width = null;
        $this->height = null;
        if (!File_Information::is_possible_file($value)) {
            $this->error(self::NOT_READABLE);
            return false;
        }
        $file = File_Information::factory($value);
        if (!$file->readable) {
            $this->error(self::NOT_READABLE);
            return false;
        }
        $this->set_value($file->client_file_name ?? $file->base_name);
        $size = getimagesize($file->path);
        if ($size === false || $size[0] === 0 || $size[1] === 0) {
            $this->error(self::NOT_DETECTED);
            return false;
        }
        $this->width = $size[0];
        $this->height = $size[1];
        if ($this->width < $this->min_width) {
            $this->error(self::WIDTH_TOO_SMALL);
        }
        if ($this->max_width !== null && $this->width > $this->max_width) {
            $this->error(self::WIDTH_TOO_BIG);
        }
        if ($this->height < $this->min_height) {
            $this->error(self::HEIGHT_TOO_SMALL);
        }
        if ($this->max_height !== null && $this->height > $this->max_height) {
            $this->error(self::HEIGHT_TOO_BIG);
        }
        if (count($this->get_messages()) > 0) {
            return false;
        }
        return true;
    }
}