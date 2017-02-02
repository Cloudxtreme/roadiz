<?php
/**
 * Copyright © 2015, Ambroise Maupate
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @file DownscaleImageManagerTest.php
 * @author Ambroise Maupate
 */

use Doctrine\Common\Collections\ArrayCollection;
use Intervention\Image\ImageManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Tests\SchemaDependentCase;
use RZ\Roadiz\Utils\Document\DownscaleImageManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class DownscaleImageManagerTest
 */
class DownscaleImageManagerTest extends SchemaDependentCase
{
    protected static $files;
    protected static $documentCollection;
    protected static $imageManager;

    public function testConstructor()
    {
        $manager = new DownscaleImageManager(
            Kernel::getService('em'),
            Kernel::getService('assetPackages'),
            Kernel::getService('logger'),
            'gd',
            1920
        );

        $this->assertNotNull($manager);
    }

    public function testProcessAndOverrideDocument()
    {
        $originalHashes = [];

        $manager = new DownscaleImageManager(
            Kernel::getService('em'),
            Kernel::getService('assetPackages'),
            Kernel::getService('logger'),
            'gd',
            100
        );

        foreach (static::$documentCollection as $key => $document) {
            $originalHashes[$key] = hash_file('md5', $document->getAbsolutePath());

            $manager->processAndOverrideDocument($document);
            $afterHash = hash_file('md5', $document->getAbsolutePath());

            if ($document->getMimeType() == 'image/gif') {
                /*
                 * GIF must be untouched
                 */
                $this->assertEquals($originalHashes[$key], $afterHash);
                $this->assertNull($document->getRawDocument());
            } else {
                /*
                 * Other must be dowscaled
                 * a raw image should be saved.
                 */
                $this->assertNotEquals($originalHashes[$key], $afterHash);
                $this->assertNotNull($document->getRawDocument());

                /*
                 * Raw document must be equal to original file
                 */
                $rawHash = hash_file('md5', $document->getRawDocument()->getAbsolutePath());
                $this->assertEquals($originalHashes[$key], $rawHash);
            }
        }

        /*
         * Removing the size cap.
         * not more raw and no more difference
         */
        $manager = new DownscaleImageManager(
            Kernel::getService('em'),
            Kernel::getService('assetPackages'),
            Kernel::getService('logger'),
            'gd',
            100000
        );

        foreach (static::$documentCollection as $key => $document) {
            $manager->processDocumentFromExistingRaw($document);
            $afterHash = hash_file('md5', $document->getAbsolutePath());

            $this->assertEquals($originalHashes[$key], $afterHash);
            $this->assertNull($document->getRawDocument());
        }
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$documentCollection = new ArrayCollection();
        $fs = new Filesystem();

        static::$imageManager = new ImageManager();
        static::$files = [
            ROADIZ_ROOT . '/tests/Fixtures/Documents/animation.gif',
            ROADIZ_ROOT . '/tests/Fixtures/Documents/lion.jpg',
            ROADIZ_ROOT . '/tests/Fixtures/Documents/dices.png',
        ];

        foreach (static::$files as $file) {
            $image = new File($file);
            $document = new Document();
            $document->setFilename($image->getBasename());
            $document->setMimeType($image->getMimeType());

            $fs->copy($file, Kernel::getInstance()->getPublicFilesPath() . '/' . $document->getFolder() . '/' . $document->getFilename());

            Kernel::getService('em')->persist($document);

            static::$documentCollection->add($document);
        }

        Kernel::getService('em')->flush();
    }
}
