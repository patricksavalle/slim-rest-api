<?php

/**
 * TRIPDRIVE.COM
 *
 * @link:       api.tripdrive.com
 * @copyright:  VCK TRAVEL BV, 2016
 * @author:     patrick@patricksavalle.com
 *
 * Note: use coding standards at http://www.php-fig.org/psr/
 */

declare(strict_types = 1);

namespace SlimRestApi\Exception;

require_once 'Exception.php';

class UploadException extends Exception
{
    public function __construct(int $upload_error, int $httpcode = 400)
    {
        $message = null;
        switch ($upload_error) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the configured server maximum";
                $httpcode = 500;
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the configured client maximum";
                $httpcode = 400;
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded, please retry";
                $httpcode = 400;
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                $httpcode = 400;
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                $httpcode = 500;
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                $httpcode = 500;
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                $httpcode = 500;
                break;
            default:
                $message = "Unknown upload error";
                $httpcode = 500;
                break;
        }
        if ($httpcode == 500) {
            error_log("File upload error {$upload_error}: {$message}");
        }
        parent::__construct($message, $httpcode);
    }
}