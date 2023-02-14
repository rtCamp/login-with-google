<?php

namespace LastCall\DownloadsPlugin\Handler;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use React\Promise\PromiseInterface;

class FileHandler extends BaseHandler
{
    const TMP_PREFIX = '.composer-extra-tmp-';

    public function createSubpackage()
    {
        $pkg = parent::createSubpackage();
        $pkg->setDistType('file');
        return $pkg;
    }

    public function getTrackingFile()
    {
        $file = basename($this->extraFile['id']) . '-' . md5($this->extraFile['id']) . '.json';
        return
            dirname($this->getTargetPath()) .
            DIRECTORY_SEPARATOR . self::DOT_DIR .
            DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function download(Composer $composer, IOInterface $io) {
        // We want to take advantage of the cache in composer's downloader, but it
        // doesn't put the file the spot we want, so we shuffle a bit.

        $cfs = new Filesystem();
        $target = $this->getTargetPath();
        $tmpDir = dirname($target) . DIRECTORY_SEPARATOR . self::TMP_PREFIX . basename($target);

        if (file_exists($tmpDir)) {
            $cfs->remove($tmpDir);
        }
        if (file_exists($target)) {
          $cfs->remove($target);
        }

        $pkg = clone $this->getSubpackage();
        $pkg->setTargetDir($tmpDir);
        $downloadManager = $composer->getDownloadManager();
        // composer:v2
        $version = method_exists(Composer::class, 'getVersion') ? Composer::getVersion() : Composer::VERSION;
        if (version_compare($version, '2.0.0') >= 0) {
          $file = '';
          $promise = $downloadManager->download($pkg, $tmpDir);
          $promise->then(static function($res) use (&$file) {
            $file = $res;
          });
          $composer->getLoop()->wait([$promise]);
          $cfs->rename($file, $target);
          $cfs->remove($tmpDir);
        }
        // composer:v1
        else {
          $downloadManager->download($pkg, $tmpDir);
          foreach ((array)glob("$tmpDir/*") as $file) {
            if (is_file($file)) {
              $cfs->rename($file, $target);
              $cfs->remove($tmpDir);
              break;
            }
          }
        }
    }

}
