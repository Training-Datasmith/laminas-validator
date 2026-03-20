<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function assert;
use function basename;
use function file_exists;
use const FILEINFO_MIME_TYPE;
use function filesize;
use finfo;
use function finfo_open;
use function is_array;
use function is_int;
use function is_readable;
use function is_string;
use Laminas\Validator\Exception\RuntimeException;
use Psr\Http\Message\Uploaded_File_Interface;
/** @internal */
final class File_Information
{
    public readonly string $base_name;
    public readonly bool $readable;
    private string|null $media_type;
    private function __construct(public readonly string $path, public readonly ?string $client_file_name, public readonly ?string $client_media_type, private Bytes|null $size)
    {
        $this->readable = is_readable($this->path);
        $this->base_name = basename($this->path);
        $this->media_type = null;
    }
    public function size(): Bytes
    {
        if ($this->size === null) {
            $bytes = filesize($this->path);
            assert(is_int($bytes));
            $this->size = Bytes::from_integer($bytes);
        }
        return $this->size;
    }
    public function detect_mime_type(): string
    {
        if ($this->media_type === null) {
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            assert($file_info instanceof finfo);
            $mime = $file_info->file($this->path);
            assert(is_string($mime));
            $this->media_type = $mime;
        }
        return $this->media_type;
    }
    public static function factory(mixed $value): self
    {
        if (!self::is_possible_file($value)) {
            throw new RuntimeException('Cannot detect any file information');
        }
        /** @psalm-var array<array-key, mixed>|string|UploadedFileInterface $value */
        if ($value instanceof Uploaded_File_Interface) {
            $path = $value->get_stream()->get_metadata('uri');
            assert(is_string($path));
            $size = $value->get_size();
            return new self($path, $value->get_client_filename(), $value->get_client_media_type(), $size === null ? $size : Bytes::from_integer($size));
        }
        if (is_string($value)) {
            return new self($value, null, null, null);
        }
        return self::from_sapi_array($value);
    }
    private static function from_sapi_array(array $value): self
    {
        $client_name = $value['name'] ?? null;
        $client_type = $value['type'] ?? null;
        $path = $value['tmp_name'] ?? null;
        $size = $value['size'] ?? null;
        assert(is_string($path));
        assert(is_string($client_name));
        assert(is_string($client_type));
        assert(is_int($size) || $size === null);
        $size = $size === null ? $size : Bytes::from_integer($size);
        return new self($path, $client_name, $client_type, $size);
    }
    public static function is_possible_file(mixed $value): bool
    {
        if ($value instanceof Uploaded_File_Interface) {
            return true;
        }
        if (is_array($value) && isset($value['tmp_name']) && is_string($value['tmp_name']) && $value['tmp_name'] !== '' && file_exists($value['tmp_name'])) {
            return true;
        }
        if (is_string($value) && $value !== '' && file_exists($value)) {
            return true;
        }
        return false;
    }
}