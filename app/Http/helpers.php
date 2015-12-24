<?php

class Helpers
{
    const SUPERUSER_ID = 1;
    const PAGINATION_DEFAULT_ITEMS = 15;
    const UPLOAD_SLUG_INITIAL_LENGTH = 4;
    const NEW_USER_DAYS = 7;
    const API_KEY_LENGTH = 64;

    // http://stackoverflow.com/questions/2510434/format-bytes-to-kilobytes-megabytes-gigabytes
    public static function format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1000));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1000, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function canHaveExif($filename)
    {
        $hasExif = false;
        if (function_exists('exif_imagetype')) {
            $hasExif = exif_imagetype($filename);
        }

        return $hasExif;
    }
}