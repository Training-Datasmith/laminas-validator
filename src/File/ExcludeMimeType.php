<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function array_merge;
use function explode;
use function in_array;
/**
 * Validator for the mime type of a file
 */
final class Exclude_Mime_Type extends Mime_Type
{
    public const FALSE_TYPE = 'fileExcludeMimeTypeFalse';
    public const NOT_DETECTED = 'fileExcludeMimeTypeNotDetected';
    public const NOT_READABLE = 'fileExcludeMimeTypeNotReadable';
    /** @var array<string, string> */
    protected array $message_templates = [self::FALSE_TYPE => "File has an incorrect mimetype of '%type%'", self::NOT_DETECTED => 'The mimetype could not be detected from the file', self::NOT_READABLE => 'File is not readable or does not exist'];
    /**
     * Returns true if the mimetype of the file does not match the given ones. Also parts
     * of mimetypes can be checked. If you give for example "image" all image
     * mime types will not be accepted like "image/gif", "image/jpeg" and so on.
     */
    public function is_valid(mixed $value): bool
    {
        if (!File_Information::is_possible_file($value)) {
            $this->error(self::NOT_READABLE);
            return false;
        }
        $file_info = File_Information::factory($value);
        $this->set_value($file_info->path);
        if (!$file_info->readable) {
            $this->error(self::NOT_READABLE);
            return false;
        }
        $this->type = $file_info->detect_mime_type();
        if (in_array($this->type, $this->mime_types, true)) {
            $this->error(self::FALSE_TYPE);
            return false;
        }
        $types = explode('/', $this->type);
        $types = array_merge($types, explode('-', $this->type));
        $types = array_merge($types, explode(';', $this->type));
        foreach ($this->mime_types as $mime) {
            if (in_array($mime, $types)) {
                $this->error(self::FALSE_TYPE);
                return false;
            }
        }
        return true;
    }
}