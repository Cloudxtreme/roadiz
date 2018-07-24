<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file RoadizExtension.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Kernel;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class RoadizExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * RoadizExtension constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        return [
            'cms_version' => Kernel::$cmsVersion,
            'cms_prefix' => Kernel::CMS_VERSION,
            'help_external_url' => 'http://docs.roadiz.io',
            'request' => $this->kernel->get('requestStack')->getCurrentRequest(),
            'is_debug' => $this->kernel->isDebug(),
            'is_preview' => $this->kernel->isPreview(),
            'is_dev_mode' => $this->kernel->isDevMode(),
            'is_prod_mode' => $this->kernel->isProdMode(),
            'bags' => [
                'settings' => $this->kernel->get('settingsBag'),
                'roles' => $this->kernel->get('rolesBag'),
                'nodeTypes' => $this->kernel->get('nodeTypesBag'),
            ]
        ];
    }
}
