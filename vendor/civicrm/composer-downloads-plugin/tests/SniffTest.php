<?php

namespace LastCall\DownloadsPlugin\Tests;

use ProcessHelper\ProcessHelper as PH;

/**
 * Class SniffTest
 * @package LastCall\DownloadsPlugin\Tests
 *
 * This is general integration test of the plugin. It creates an example project which uses the
 * current/under-development plugin. The example project includes a handful of downloads which
 * employ a cross-section of the configuration options.
 */
class SniffTest extends IntegrationTestCase
{

    public static function getComposerJson() {
        return parent::getComposerJson() + [
            'name' => 'test/sniff-test',
            'require' => [
                'civicrm/composer-downloads-plugin' => '@dev',
            ],
            'minimum-stability' => 'dev',
            'extra' => [
                'downloads' => [
                    '*' => [
                        'path' => 'extern/{$id}',
                    ],
                    'README' => [
                        'url' => 'https://github.com/composer/composer/raw/1.9.0/README.md',
                        'path' => 'docs/README.md'
                    ],
                    'jquery-full' => [
                        'url' => 'https://github.com/civicrm/jquery/archive/1.12.4-civicrm-1.2.zip',
                    ],
                    'jquery-lesser' => [
                        'version' => '1.12.4-civicrm-1.2',
                        'url' => 'https://github.com/civicrm/jquery/archive/{$version}.zip',
                        'path' => 'extern/jquery-lesser',
                        'ignore' => ['Gruntfile.js']
                    ],
                    'cv' => [
                        'type' => 'phar',
                        'url' => 'https://download.civicrm.org/cv/cv.phar-2019-08-20-14fe9da8',
                        'path' => 'bin/cv',
                    ],
                ],
            ],
        ];
    }

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::initTestProject(static::getComposerJson());
        $composer_path = self::getComposerPath();
        PH::runOk("$composer_path install -v");
    }

    public function getExampleChecksums() {
        return [
            ['docs/README.md', 'docs/README.md', '1d0577cc52d55f0680b431184e898f0cbcb927e52e843a319d7122db9be72813'],
            ['extern/jquery-full', 'extern/jquery-full/dist/jquery.js', '5f2caf09052782caf67e1772c0abce31747ffbc7a1c50690e331b99c7d9ea8dc'],
            ['extern/jquery-full', 'extern/jquery-full/Gruntfile.js', '3508ff74f8ef106a80f25f28f44a20c47a2b67d84396bb141928ff978ba4012e'],
            ['extern/jquery-lesser', 'extern/jquery-lesser/dist/jquery.js', '5f2caf09052782caf67e1772c0abce31747ffbc7a1c50690e331b99c7d9ea8dc'],
            ['extern/jquery-lesser', 'extern/jquery-lesser/Gruntfile.js', NULL],
            ['bin/cv', 'bin/cv', 'bf162d5d7dd0bef087d7dd07f474039b2e25c4bcca328a2b2097958ac6294476']
        ];
    }

    /**
     * Ensure that the file checksums match expectations with both (a) original download and (b) re-download.
     *
     * @param string $file
     * @param string|NULL $sha256
     *   The expected content of the file, or NULL if the file should not exist.
     * @dataProvider  getExampleChecksums
     */
    public function testDownloadAndRedownload($path, $file, $sha256) {
        // Initial download
        $this->assertFileChecksum($file, $sha256, 'Initial');

        // Force re-download
        if (is_dir($path)) {
            self::cleanDir($path);
        }
        else {
            unlink($path);
        }
        $this->assertFileNotExists($file);
        $composer_path = self::getComposerPath();
        PH::runOk("$composer_path install -v");

        // And make sure it all worked out...
        $this->assertFileChecksum($file, $sha256, 'Redownload');
    }

    public function assertFileChecksum($file, $sha256, $message = NULL) {
        if ($sha256 === NULL) {
            $this->assertFileNotExists($file, "($message) File should not exist");
        }
        else {
            $this->assertFileExists($file, "($message) File should exist");
            $this->assertEquals($sha256, hash('sha256', file_get_contents($file)), "($message) File should given checksum");
        }
    }

    private static function getComposerPath() {
      return realpath(__DIR__ . '/../vendor/bin/composer');
    }

}
