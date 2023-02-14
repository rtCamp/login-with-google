<?php

/*
 * This file is part of Composer Extra Files Plugin.
 *
 * (c) 2017 Last Call Media, Rob Bayliss <rob@lastcallmedia.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LastCall\DownloadsPlugin\Tests;

use Composer\Package\Package;
use LastCall\DownloadsPlugin\Subpackage;
use LastCall\DownloadsPlugin\DownloadsParser;
use PHPUnit\Framework\TestCase;

class DownloadsParserTest extends TestCase
{
    private function getPackage(array $extra = [])
    {
        $package = new Package('foo', '1.0.0', '1.0.0');
        $package->setExtra([
            'downloads' => $extra,
        ]);

        return $package;
    }

    public function testIgnoresPackagesWithoutDownloads()
    {
        $package = new Package('foo', '1.0.0', '1.0.0');
        $parser = new DownloadsParser();
        $this->assertEquals([], $parser->parse($package, "/EXAMPLE"));
    }

    public function testAddsFiles()
    {
        $package = $this->getPackage([
            'bar' => ['url' => 'foo', 'path' => 'bar'],
        ]);
        $expectSubpackage = new Subpackage($package, 'bar', 'foo', 'file', 'bar');
        $actualSubpackage = (new DownloadsParser())->parse($package, "/EXAMPLE")[0]->getSubpackage();
        $this->assertEquals([$expectSubpackage], [$actualSubpackage]);
    }

    public function getDownloadTypeTests()
    {
        return [
            ['zip', 'foo.zip'],
            ['zip', 'foo.zip?foo'],
            ['zip', 'http://example.com/foo.zip?abc#def'],
            ['tar', 'foo.tar.gz'],
            ['tar', 'http://example.com/foo.tar.gz?abc#def'],
            ['tar', 'foo.tgz'],
            ['file', 'foo'],
        ];
    }

    /**
     * @dataProvider getDownloadTypeTests
     */
    public function testSetsDownloadType($expectedType, $url)
    {
        $package = $this->getPackage([
            'bar' => ['url' => $url, 'path' => 'bar'],
        ]);
        $parsed = (new DownloadsParser())->parse($package, "/EXAMPLE");
        $this->assertEquals($expectedType, $parsed[0]->getSubpackage()->getDistType());
    }
}
