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
 * @file MixedUrlMatcher.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Translation;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Extends compiled UrlMatcher to add a dynamic routing feature which deals
 * with NodesSources URL.
 */
class MixedUrlMatcher extends \GlobalUrlMatcher
{
    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        Kernel::getService('stopwatch')->start('matchingRoute');
        $decodedUrl = rawurldecode($pathinfo);

        try {
            /*
             * Try STATIC routes
             */
            return parent::match($pathinfo);

        } catch (ResourceNotFoundException $e) {
            /*
             * Try nodes routes
             */
            if (false !== $ret = $this->matchNode($decodedUrl)) {
                return $ret;
            } else {
                return array(
                    '_controller' => $this->getThemeController().'::throw404',
                    'message' => 'Unable to find any matching route nor matching node.'.
                                 ' Check your `Resources/routes.yml` file.',
                    'node'        => null,
                    'translation' => null
                );
            }
        }
    }

    /**
     * @param string $decodedUrl
     *
     * @return array
     */
    private function matchNode($decodedUrl)
    {
        $tokens = explode('/', $decodedUrl);
        // Remove empty tokens (especially when a trailing slash is present)
        $tokens = array_values(array_filter($tokens));

        /*
         * Try with URL Aliases
         */
        $node = $this->parseFromUrlAlias($tokens);
        if ($node !== null) {

            $translation = $node->getNodeSources()->first()->getTranslation();
            Kernel::getInstance()->getRequest()->setLocale($translation->getLocale());

            return array(
                '_controller' => $this->getThemeController().'::indexAction',
                'node'        => $node,
                'translation' => $translation
            );
        } else {
            /*
             * Try with node name
             */
            $translation = $this->parseTranslation($tokens);

            if (null !== $translation) {
                Kernel::getInstance()->getRequest()->setLocale($translation->getLocale());
            }

            $node = $this->parseNode($tokens, $translation);
            if ($node !== null) {
                /*
                 * Try with nodeName
                 */
                return array(
                    '_controller' => $this->getThemeController().'::indexAction',
                    'node'        => $node,
                    'translation' => $translation
                );
            } else {
                return false;
            }
        }
    }

    /**
     * Get Theme front controller class FQN.
     *
     * @return string Full qualified Classname
     */
    public function getThemeController()
    {
        $host = $this->context->getHost();
        /*
         * First we look for theme according to hostname.
         */
        $theme = Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Theme')
                        ->findAvailableFrontendWithHost($host);

        /*
         * If no theme for current host, we look for
         * any frontend available theme.
         */
        if (null === $theme) {
            $theme = Kernel::getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Theme')
                            ->findFirstAvailableFrontend();
        }

        if (null !== $theme) {
            return $theme->getClassName();
        } else {
            return 'RZ\Roadiz\CMS\Controllers\FrontendController';
        }
    }

    /**
     * Parse URL searching nodeName.
     *
     * @param array       &$tokens
     * @param Translation $translation
     *
     * @return RZ\Roadiz\Core\Entities\Node
     */
    private function parseNode(&$tokens, Translation $translation)
    {
        if (!empty($tokens[0])) {

            /*
             * If the only url token is for language, return Home page
             */
            if (in_array($tokens[0], Translation::getAvailableLocalesShortcuts()) &&
                count($tokens) == 1) {
                return Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Node')
                        ->findHomeWithTranslation($translation);
            } else {
                $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);

                if ($identifier !== null &&
                    $identifier != '') {

                    return Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Node')
                        ->findByNodeNameWithTranslation($identifier, $translation);
                }
            }
        }

        return null;
    }

    /**
     * Parse URL searching UrlAlias.
     *
     * @param array &$tokens [description]
     *
     * @return RZ\Roadiz\Core\Entities\Node
     */
    private function parseFromUrlAlias(&$tokens)
    {
        if (!empty($tokens[0])) {

            /*
             * If the only url token if for language, return no url alias !
             */
            if (in_array($tokens[0], Translation::getAvailableLocalesShortcuts()) &&
                count($tokens) == 1) {
                return null;
            } else {
                $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);

                if ($identifier != '') {

                    $ua = Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\UrlAlias')
                        ->findOneBy(array('alias'=>$identifier));

                    if ($ua !== null) {
                        return Kernel::getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Node')
                            ->findOneWithUrlAlias($ua);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse translation from URL tokens.
     *
     * @param array &$tokens
     *
     * @return RZ\Roadiz\Core\Entities\Translation
     */
    private function parseTranslation(&$tokens)
    {
        if (!empty($tokens[0])) {

            $firstToken = $tokens[0];
            /*
             * First token is for language
             */
            if (in_array($firstToken, Translation::getAvailableLocales())) {
                $locale = strip_tags($firstToken);

                if ($locale !== null && $locale != '') {
                    return Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                        ->findOneByLocale($locale);
                }
            }
        }

        return Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                        ->findDefault();
    }
}
