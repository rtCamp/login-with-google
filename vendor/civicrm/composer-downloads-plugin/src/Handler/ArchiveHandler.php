<?php

namespace LastCall\DownloadsPlugin\Handler;

use Composer\Composer;
use Composer\IO\IOInterface;
use LastCall\DownloadsPlugin\GlobCleaner;

class ArchiveHandler extends BaseHandler
{

    public function createSubpackage()
    {
        $pkg = parent::createSubpackage();
        $pkg->setDistType($this->parseDistType($this->extraFile['url']));
        return $pkg;
    }

    protected function parseDistType($url)
    {
        $parts = parse_url($url);
        $filename = pathinfo($parts['path'], PATHINFO_BASENAME);
        if (preg_match('/\.zip$/', $filename)) {
            return 'zip';
        } elseif (preg_match('/\.(tar\.gz|tgz)$/', $filename)) {
            return 'tar';
        } else {
            throw new \RuntimeException("Failed to determine archive type for $filename");
        }
    }

    public function getTrackingFile()
    {
        $file = basename($this->extraFile['id']) . '-' . md5($this->extraFile['id']) . '.json';
        return
            $this->getTargetPath() .
            DIRECTORY_SEPARATOR . self::DOT_DIR .
            DIRECTORY_SEPARATOR . $file;
    }

    public function createTrackingData()
    {
        $meta = parent::createTrackingData();
        $meta['ignore'] = $this->findIgnores();
        return $meta;
    }


    public function getChecksum() {
        $ignore = empty($this->extraFile['ignore']) ? [] : array_values($this->extraFile['ignore']);
        sort($ignore);
        return hash('sha256', parent::getChecksum() . serialize($ignore));
    }

    /**
     * @return string[]|NULL
     *   List of files to exclude. Use '**' to match subdirectories.
     *   Ex: ['.gitignore', '*.md']
     */
    public function findIgnores()
    {
        return isset($this->extraFile['ignore'])
            ? $this->extraFile['ignore']
            : NULL;
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function download(Composer $composer, IOInterface $io)
    {
        $targetPath = $this->getTargetPath();
        $downloadManager = $composer->getDownloadManager();

        // In composer:v2, download and extract were separated.
        $version = method_exists(Composer::class, 'getVersion') ? Composer::getVersion() : Composer::VERSION;
        if (version_compare($version, '2.0.0') >= 0) {
          $promise = $downloadManager->download($this->getSubpackage(), $targetPath);
          $composer->getLoop()->wait([$promise]);
          $promise = $downloadManager->install($this->getSubpackage(), $targetPath);
          $composer->getLoop()->wait([$promise]);
        } else {
          $downloadManager->download($this->getSubpackage(), $targetPath);
        }
        GlobCleaner::clean($io, $targetPath, $this->findIgnores());
    }

}
