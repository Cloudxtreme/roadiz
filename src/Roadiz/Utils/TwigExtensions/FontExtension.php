<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file FontExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Font;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension that allow render fonts.
 */
class FontExtension extends AbstractExtension
{
    /**
     * @var Container
     */
    private $container;

    /**
     * DocumentExtension constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('eotPath', [$this, 'getEotFilePath']),
            new TwigFilter('ttfPath', [$this, 'getTtfFilePath']),
            new TwigFilter('otfPath', [$this, 'getTtfFilePath']),
            new TwigFilter('svgPath', [$this, 'getSvgFilePath']),
            new TwigFilter('woffPath', [$this, 'getWoffFilePath']),
            new TwigFilter('woff2Path', [$this, 'getWoff2FilePath']),
        ];
    }

    /**
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getEotFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getEOTRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getTtfFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getOTFRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getSvgFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getSVGRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getWoffFilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getWOFFRelativeUrl());
    }

    /**
     * @param Font $font
     * @return string
     * @throws RuntimeError
     */
    public function getWoff2FilePath(Font $font = null)
    {
        if (null === $font) {
            throw new RuntimeError('Font can’t be null.');
        }
        return $this->container['assetPackages']->getFontsPath($font->getWOFF2RelativeUrl());
    }
}
