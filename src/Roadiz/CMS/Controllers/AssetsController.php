<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file AssetsController.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Controllers;

use AM\InterventionRequest\Configuration;
use AM\InterventionRequest\InterventionRequest;
use AM\InterventionRequest\ShortUrlExpander;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\YamlFileLoader;

/**
 * Special controller app file for assets managment with InterventionRequest lib.
 */
class AssetsController extends AppController
{
    /**
     * Initialize controller with NO twig environment.
     */
    public function __init()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function prepareBaseAssignation()
    {

    }

    /**
     * {@inheritdoc}
     */
    public static function getRoutes()
    {
        $locator = new FileLocator([
            ROADIZ_ROOT . '/src/Roadiz/CMS/Resources',
        ]);

        if (file_exists(ROADIZ_ROOT . '/src/Roadiz/CMS/Resources/assetsRoutes.yml')) {
            $loader = new YamlFileLoader($locator);

            return $loader->load('assetsRoutes.yml');
        }

        return null;
    }

    /**
     *
     * @param  Request $request
     * @param  string  $queryString
     * @param  string  $filename
     * @return Response
     */
    public function interventionRequestAction(Request $request, $queryString, $filename)
    {
        $log = new Logger('InterventionRequest');
        $log->pushHandler(new StreamHandler(ROADIZ_ROOT . '/logs/interventionRequest.log', Logger::INFO));

        try {
            $cacheDir = ROADIZ_ROOT . '/cache/rendered';
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir);
            }
            $conf = new Configuration();
            $conf->setCachePath($cacheDir);
            $conf->setImagesPath(ROADIZ_ROOT . '/files');

            /*
             * Handle short url with Url rewriting
             */
            $expander = new ShortUrlExpander($request);
            $expander->injectParamsToRequest($queryString, $filename);

            /*
             * Handle main image request
             */
            $iRequest = new InterventionRequest($conf, $request, $log);
            $iRequest->handle();
            return $iRequest->getResponse();
        } catch (\Exception $e) {
            if (null !== $log) {
                $log->error($e->getMessage());
            }
            return new Response(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'text/plain']
            );
        }
    }

    /**
     * Request a single protected font file from Roadiz.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $filename
     * @param string                                   $extension
     * @param string                                   $token
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function fontFileAction(Request $request, $filename, $variant, $extension, $token)
    {
        $font = $this->getService('em')
                     ->getRepository('RZ\Roadiz\Core\Entities\Font')
                     ->findOneBy(['hash' => $filename, 'variant' => $variant]);

        if (null !== $font) {
            if ($this->getService('csrfProvider')->isCsrfTokenValid($font->getHash() . $font->getVariant(), $token)) {
                switch ($extension) {
                    case 'eot':
                        $fontpath = $font->getEOTAbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['eot'];
                        break;
                    case 'woff':
                        $fontpath = $font->getWOFFAbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['woff'];
                        break;
                    case 'woff2':
                        $fontpath = $font->getWOFF2AbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['woff2'];
                        break;
                    case 'svg':
                        $fontpath = $font->getSVGAbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['svg'];
                        break;
                    case 'otf':
                    case 'ttf':
                        $fontpath = $font->getOTFAbsolutePath();
                        $mime = \RZ\Roadiz\Core\Entities\Font::$extensionToMime['otf'];
                        break;
                    default:
                        $fontpath = "";
                        $mime = "text/html";
                        break;
                }

                if ("" != $fontpath) {
                    $response = new Response(
                        file_get_contents($fontpath),
                        Response::HTTP_OK,
                        ['content-type' => $mime]
                    );
                    $date = new \DateTime();
                    $date->modify('+2 hours');
                    $response->setExpires($date);
                    $response->setPrivate(true);
                    $response->setMaxAge(60 * 60 * 2);

                    return $response;
                }
            } else {
                return new Response(
                    "Font Fail " . $token,
                    Response::HTTP_NOT_FOUND,
                    ['content-type' => 'text/html']
                );
            }

        } else {
            return new Response(
                "Font doesn't exist " . $filename,
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'text/html']
            );
        }
    }

    /**
     * Request the font-face CSS file listing available fonts.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param string                                   $token
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function fontFacesAction(Request $request, $token)
    {
        $repository = $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\Font');
        $lastMod = $repository->getLatestUpdateDate();

        $response = new Response(
            '',
            Response::HTTP_NOT_MODIFIED,
            ['content-type' => 'text/css']
        );
        $response->setCache([
            'last_modified' => new \DateTime($lastMod),
            'max_age' => 60 * 60 * 2,
            'public' => false,
        ]);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $fonts = $repository->findAll();

        $fontOutput = [];

        foreach ($fonts as $font) {
            $fontOutput[] = $font->getViewer()->getCSSFontFace($this->getService('csrfProvider'));
        }

        $response->setContent(implode(PHP_EOL, $fontOutput));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
