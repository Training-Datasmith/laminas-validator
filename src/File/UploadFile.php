<?php

declare (strict_types=1);
namespace Laminas\Validator\File;

use function basename;
use function is_array;
use function is_file;
use function is_int;
use function is_string;
use function is_uploaded_file;
use Laminas\Validator\Abstract_Validator;
use Psr\Http\Message\Uploaded_File_Interface;
use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_OK;
use const UPLOAD_ERR_PARTIAL;
/**
 * Validator for the maximum size of a file up to a max of 2GB
 */
final class Upload_File extends Abstract_Validator
{
    /**
     * @const string Error constants
     */
    public const INI_SIZE = 'fileUploadFileErrorIniSize';
    public const FORM_SIZE = 'fileUploadFileErrorFormSize';
    public const PARTIAL = 'fileUploadFileErrorPartial';
    public const NO_FILE = 'fileUploadFileErrorNoFile';
    public const NO_TMP_DIR = 'fileUploadFileErrorNoTmpDir';
    public const CANT_WRITE = 'fileUploadFileErrorCantWrite';
    public const EXTENSION = 'fileUploadFileErrorExtension';
    public const ATTACK = 'fileUploadFileErrorAttack';
    public const FILE_NOT_FOUND = 'fileUploadFileErrorFileNotFound';
    public const UNKNOWN = 'fileUploadFileErrorUnknown';
    /** @var array<string, string> */
    protected array $message_templates = [self::INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini', self::FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was ' . 'specified in the HTML form', self::PARTIAL => 'The uploaded file was only partially uploaded', self::NO_FILE => 'No file was uploaded', self::NO_TMP_DIR => 'Missing a temporary folder', self::CANT_WRITE => 'Failed to write file to disk', self::EXTENSION => 'A PHP extension stopped the file upload', self::ATTACK => 'File was illegally uploaded. This could be a possible attack', self::FILE_NOT_FOUND => 'File was not found', self::UNKNOWN => 'Unknown error while uploading file'];
    /**
     * Returns true if and only if the file was uploaded without errors
     */
    public function is_valid(mixed $value): bool
    {
        if (is_array($value) && isset($value['tmp_name'], $value['name'], $value['error']) && is_string($value['tmp_name']) && is_string($value['name']) && is_int($value['error'])) {
            return $this->validate_uploaded_file($value['error'], $value['name'], $value['tmp_name']);
        }
        if ($value instanceof Uploaded_File_Interface) {
            return $this->validate_psr7uploaded_file($value);
        }
        if (is_string($value)) {
            return $this->validate_uploaded_file(0, basename($value), $value);
        }
        $this->error(self::UNKNOWN);
        return false;
    }
    private function validate_file_from_error_code(int $error): bool
    {
        switch ($error) {
            case UPLOAD_ERR_OK:
                return true;
            case UPLOAD_ERR_INI_SIZE:
                $this->error(self::INI_SIZE);
                return false;
            case UPLOAD_ERR_FORM_SIZE:
                $this->error(self::FORM_SIZE);
                return false;
            case UPLOAD_ERR_PARTIAL:
                $this->error(self::PARTIAL);
                return false;
            case UPLOAD_ERR_NO_FILE:
                $this->error(self::NO_FILE);
                return false;
            case UPLOAD_ERR_NO_TMP_DIR:
                $this->error(self::NO_TMP_DIR);
                return false;
            case UPLOAD_ERR_CANT_WRITE:
                $this->error(self::CANT_WRITE);
                return false;
            case UPLOAD_ERR_EXTENSION:
                $this->error(self::EXTENSION);
                return false;
            default:
                $this->error(self::UNKNOWN);
                return false;
        }
    }
    /**
     * @param int $error UPLOAD_ERR_* constant
     * @param string $filename Client file name
     * @param string $uploadedFile File path
     */
    private function validate_uploaded_file(int $error, string $filename, string $uploaded_file): bool
    {
        $this->set_value($filename);
        // Normal errors can be validated normally
        if ($error !== UPLOAD_ERR_OK) {
            return $this->validate_file_from_error_code($error);
        }
        // Did we get no name? Is the file missing?
        if (empty($uploaded_file) || false === is_file($uploaded_file)) {
            $this->error(self::FILE_NOT_FOUND);
            return false;
        }
        // Do we have an invalid upload?
        if (!is_uploaded_file($uploaded_file)) {
            $this->error(self::ATTACK);
            return false;
        }
        return true;
    }
    private function validate_psr7uploaded_file(Uploaded_File_Interface $uploaded_file): bool
    {
        $this->set_value($uploaded_file);
        return $this->validate_file_from_error_code($uploaded_file->get_error());
    }
}