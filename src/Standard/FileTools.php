<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library\Standard;

use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Exceptions\SystemException;

class FileTools
{
    /**
     * Return file extension
     *
     * @param string $path
     * @return string
     */
    public static function getExtension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * @param float $kb
     * @return string
     */
    public static function displayFileSize(float $kb): string
    {
        $units = array('KB', 'MB', 'GB', 'TB');

        $pow = floor(($kb ? log($kb) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $kb /= pow(1024, $pow);

        return number_format($kb, 2, '.', '') . ' ' . $units[$pow];
    }

    /**
     * @param string $file_path
     * @return array
     */
    public static function getImageResolution(string $file_path): array
    {
        $data = getimagesize($file_path);

        return array(
            'width' => $data[0],
            'height' => $data[1],
        );
    }

    /**
     * Remove files and folders
     *
     * @param string $path
     * @param bool $remove_current_dir
     * @return void
     */
    public static function removeRecursive(string $path, bool $remove_current_dir = true): void
    {
        $array = array_diff(scandir($path), array('..', '.'));

        foreach ($array as $item) {
            $item_path = $path . '/' . $item;

            if (is_dir($item_path)) {
                self::removeRecursive($item_path, true);
            } else {
                self::removeFile($item_path);
            }
        }

        if ($remove_current_dir === true) {
            rmdir($path);
        }
    }

    /**
     * Copy folder & files
     *
     * @param string $src
     * @param string $dest
     * @param int $chmod
     * @return void
     * @throws ConfigException
     */
    public static function copyRecursive(string $src, string $dest, int $chmod = 0755): void
    {
        if (is_file($src)) {
            copy($src, $dest);

            return;
        }

        if (is_dir($dest) === false) {
            self::createFolder($dest, $chmod);
        }

        $array = array_diff(scandir($src), array('..', '.'));

        foreach ($array as $item) {
            self::copyRecursive($src . '/' . $item, $dest . '/' . $item, $chmod);
        }
    }

    /**
     * @param string $path
     * @param int $chmod
     * @return void
     * @throws ConfigException
     */
    public static function createFolder(string $path, int $chmod = 0755): void
    {
        $arr_dir = array();

        self::buildPathList($arr_dir, $path);

        $arr_dir = array_reverse($arr_dir);

        foreach ($arr_dir as $dir) {
            if (mkdir($dir, $chmod) === false) {
                throw new SystemException('Unable to create folder (eg: ' . $path . ')');
            }
        }
    }

    /**
     * @param $arr
     * @param $path
     * @return void
     */
    private static function buildPathList(&$arr, $path)
    {
        if (!file_exists($path)) {
            $arr[] = $path;

            self::buildPathList($arr, dirname($path));
        }
    }

    /**
     * @param string $absolute_path
     * @return bool
     */
    public static function removeFile(string $absolute_path): bool
    {
        return unlink($absolute_path);
    }
}
