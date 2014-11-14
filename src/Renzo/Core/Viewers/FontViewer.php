<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file FontViewer.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Viewers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Font;
use RZ\Renzo\Core\Bags\SettingsBag;

use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * FontViewer
 */
class FontViewer implements ViewableInterface
{
    protected $font = null;
    protected $twig = null;

    /**
     * @param RZ\Renzo\Core\Entities\Font $font
     */
    public function __construct(Font $font)
    {
        $this->font = $font;
    }

    /**
     * @return Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Get twig cache folder for current Viewer
     *
     * @return string
     */
    public function getCacheDirectory()
    {
        return RENZO_ROOT.'/cache/twig_cache';
    }

    /**
     * @{inheritdoc}
     */
    public function initializeTranslator()
    {

    }


    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return Kernel::getService('twig.environment');
    }

    /**
     * Get CSS font-face properties for current font.
     *
     * @param Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider $csrfProvider
     *
     * @return string CSS output
     */
    public function getCSSFontFace(SessionCsrfProvider $csrfProvider)
    {
        $assignation = array(
            'font' => $this->font,
            'site' => SettingsBag::get('site_name'),
            'fontFolder' => '/'.Font::getFilesFolderName(),
            'csrfProvider' => $csrfProvider
        );

        return $this->getTwig()->render('fonts/fontfamily.css.twig', $assignation);
    }
}
